<?php
/**
 * QUEEN Cosmetics — fonctions utilitaires partagées (espace client)
 */
if (!defined('APP_ROOT')) { define('APP_ROOT', dirname(__DIR__)); }
require_once __DIR__ . '/security.php';
security_boot();
require_once __DIR__ . '/lang.php';
lang_boot();   // en-têtes de sécurité + session durcie

/* ───────────── Sortie & URL ───────────── */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function base_url(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($dir, '/');
}
function asset(string $path): string { return base_url() . '/' . ltrim($path, '/'); }
function url(string $page = 'accueil', array $params = []): string {
    return base_url() . '/index.php?' . http_build_query(['action' => $page] + $params);
}
function money($n): string { return number_format((float)$n, 2, ',', ' ') . ' DH'; }
function initial(string $s): string {
    $s = trim($s);
    if ($s === '') return '?';
    return function_exists('mb_substr') ? mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper($s[0]);
}
/** Image d'un produit / publicité : public/images/<fichier> */
function img_url(?string $file): string {
    $file = trim((string)$file);
    return $file === '' ? '' : asset('images/' . rawurlencode(basename($file)));
}

function redirect(string $page, array $params = []): void {
    header('Location: ' . url($page, $params));
    exit;
}
function redirect_back(string $fallback = 'accueil'): void {
    $ref  = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url($ref, PHP_URL_HOST);
    if ($host && $host === parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

/* ───────────── Messages flash ───────────── */
function flash_add(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_pull(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ───────────── AJAX / JSON ───────────── */
function is_ajax(): bool { return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'; }
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(lang_json($data), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ───────────── CSRF ───────────── */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
/** Jeton valide ET requête issue de notre propre site (contrôle de l'en-tête Origin) */
function csrf_valid(): bool {
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || $sent === '' || !hash_equals(csrf_token(), $sent)) return false;
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $oh = parse_url($origin, PHP_URL_HOST);
        $mh = parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        if (!$oh || !$mh || strcasecmp($oh, $mh) !== 0) return false;
    }
    return true;
}
function csrf_check(): void {
    if (csrf_valid()) return;
    audit('csrf_echec', (string)($_GET['action'] ?? ''));
    if (is_ajax()) json_out(['ok' => false, 'message' => 'Session expirée, rechargez la page.'], 419);
    flash_add('error', 'Session expirée, veuillez réessayer.');
    redirect_back('accueil');
}

/* ───────────── Base de données ─────────────
 * Utilise config/database.php. Ce fichier doit soit RETOURNER un objet PDO
 * (return new PDO(...);), soit définir une variable $pdo / $conn / $db.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $ret = require_once APP_ROOT . '/config/database.php';
    $found = $ret instanceof PDO ? $ret : null;
    if (!$found) {
        foreach (['pdo', 'conn', 'db', 'connexion', 'bdd'] as $n) {
            if (isset($$n) && $$n instanceof PDO)                   { $found = $$n; break; }
            if (isset($GLOBALS[$n]) && $GLOBALS[$n] instanceof PDO) { $found = $GLOBALS[$n]; break; }
        }
    }
    if (!$found) {
        throw new RuntimeException("config/database.php doit retourner un objet PDO (ou définir \$pdo).");
    }
    $found->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $found->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);      // vraies requêtes préparées (anti injection SQL)
    $found->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo = $found;
}

/* ───────────── Utilisateur connecté ─────────────
 * Lit la session créée par AuthController (plusieurs noms de clés acceptés).
 */
function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;

    $s  = $_SESSION['user'] ?? $_SESSION['utilisateur'] ?? null;
    $id = is_array($s) ? ($s['id'] ?? $s['user_id'] ?? null)
                       : ($_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['utilisateur_id'] ?? $_SESSION['id_utilisateur'] ?? null);
    if (!$id || !is_numeric($id)) return $cache = null;

    // Le rôle n'est JAMAIS pris dans la session : relu en base à chaque requête.
    // → un rôle modifié ou un compte supprimé par l'admin prend effet immédiatement.
    $st = db()->prepare('SELECT id, nom, email, role FROM utilisateurs WHERE id = ?');
    $st->execute([(int)$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        unset($_SESSION['user'], $_SESSION['utilisateur'], $_SESSION['user_id'], $_SESSION['id'], $_SESSION['utilisateur_id'], $_SESSION['id_utilisateur']);
        return $cache = null;
    }
    return $cache = ['id' => (int)$row['id'], 'nom' => (string)$row['nom'], 'email' => (string)$row['email'], 'role' => (string)($row['role'] ?: 'client')];
}

/* ───────────── Notifications (suivi « déjà vu » par cookie) ───────────── */
function notif_cookie(int $uid): string { return 'queen_seen_' . $uid; }
function notif_seen(int $uid): array {
    $d = json_decode($_COOKIE[notif_cookie($uid)] ?? '', true);
    return is_array($d) ? $d : [];
}
function notif_mark_seen(int $uid, array $statuts): void {      // $statuts : id => statut
    $json = json_encode((object)array_slice($statuts, 0, 100, true));
    if (!headers_sent()) {
        setcookie(notif_cookie($uid), $json, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true]);
    }
    $_COOKIE[notif_cookie($uid)] = $json;
}

/** Libellé / classe CSS / message d'un statut de commande */
function statut_info(string $s): array {
    static $map = [
        'attente' => ['En attente', 'attente',  'Votre commande a bien été reçue. Elle attend la validation de notre équipe.'],
        'acceptee'=> ['Acceptée',   'acceptee', 'Bonne nouvelle ! Votre commande a été acceptée et est en préparation.'],
        'refusee' => ['Refusée',    'refusee',  'Désolé, votre commande n\'a pas pu être acceptée. Contactez-nous pour plus d\'informations.'],
        'livree'  => ['Livrée',     'livree',   'Votre commande a été livrée. Merci de votre confiance !'],
    ];
    // Normalise (casse/accents) et accepte les statuts de l'admin : En attente / Confirmé / Annulé (+ variantes)
    $k = strtolower(trim(strtr($s, ['é'=>'e','è'=>'e','ê'=>'e','É'=>'e','à'=>'a','É'=>'e'])));
    $alias = [
        'en attente'=>'attente','attente'=>'attente',
        'confirme'=>'acceptee','confirmee'=>'acceptee','acceptee'=>'acceptee','accepte'=>'acceptee',
        'annule'=>'refusee','annulee'=>'refusee','refusee'=>'refusee','refuse'=>'refusee',
        'livre'=>'livree','livree'=>'livree',
    ];
    $key = $alias[$k] ?? 'attente';
    return $map[$key];
}

/* ───────────── Données communes de la barre de navigation ───────────── */
function nav_data(): array {
    $user  = current_user();
    $notif = 0;
    if ($user) {
        $seen = notif_seen($user['id']);
        foreach ((new Commande())->statutsPourClient($user['id']) as $id => $st) {
            if (($seen[$id] ?? null) !== $st) $notif++;
        }
    }
    return ['user' => $user, 'cartCount' => Panier::compter(), 'notifCount' => $notif];
}

/* ───────────── Rendu d'une vue avec header + footer ───────────── */
function render(string $view, array $data = [], string $active = ''): void {
    $nav = nav_data();
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/app/views/layouts/header.php';
    require APP_ROOT . '/app/views/' . $view . '.php';
    require APP_ROOT . '/app/views/layouts/footer.php';
}


/* ───────────── Espace personnel (admin / responsables) ───────────── */
const ROLES_LABELS = [
    'admin'                 => 'Administrateur',
    'responsable_produits'  => 'Responsable produits',
    'responsable_commandes' => 'Responsable commandes',
    'client'                => 'Client',
];
/** Page d'accueil de chaque rôle (mêmes routes que AuthController::redirectByRole) */
function staff_home(string $role): string {
    return [
        'admin'                 => 'admin_dashboard',
        'responsable_produits'  => 'produits_liste',
        'responsable_commandes'  => 'commandes_gestion',
    ][$role] ?? 'accueil';
}
/** Bloque l'accès si non connecté ou si le rôle n'est pas autorisé */
function require_role(array $roles): array {
    $u = current_user();
    if (!$u) { flash_add('error', 'Veuillez vous connecter.'); redirect('login'); }
    if (!in_array($u['role'], $roles, true)) {
        audit('acces_refuse', (string)($_GET['action'] ?? '') . ' role=' . $u['role']);
        flash_add('error', "Accès refusé : cette page n'est pas accessible avec votre rôle.");
        redirect(staff_home($u['role']));
    }
    return $u;
}
function staff_render(string $view, array $data = [], string $active = ''): void {
    $user = current_user();
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/app/views/layouts/staff_header.php';
    require APP_ROOT . '/app/views/' . $view . '.php';
    require APP_ROOT . '/app/views/layouts/staff_footer.php';
}
function stock_info(int $q): array {      // [libellé, classe pill]
    if ($q <= 0) return ['Rupture', 'refusee'];
    if ($q <= 5) return ['Stock faible', 'attente'];
    return ['Disponible', 'livree'];
}

/** Enregistre l'image envoyée (champ $field) dans public/images/ ; null si aucun fichier. Lève RuntimeException si invalide. */
function upload_image(string $field, string $nom): ?string {
    $f = $_FILES[$field] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK)  throw new RuntimeException("L'envoi de l'image a échoué (fichier trop lourd ?).");
    if ($f['size'] > 3 * 1024 * 1024)   throw new RuntimeException("L'image ne doit pas dépasser 3 Mo.");

    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime  = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$mime])) throw new RuntimeException("Format d'image non accepté (JPG, PNG, WEBP ou GIF).");

    $dim = @getimagesize($f['tmp_name']);                       // doit être une vraie image décodable
    if (!$dim || $dim[0] < 1 || $dim[1] < 1 || $dim[0] > 6000 || $dim[1] > 6000) throw new RuntimeException("Image invalide ou trop grande (6000 px maximum).");
    if (!is_uploaded_file($f['tmp_name']))    throw new RuntimeException("Envoi de fichier invalide.");

    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom) ?: 'image'), '-')) ?: 'image';
    $name = substr($slug, 0, 40) . '-' . bin2hex(random_bytes(4)) . '.' . $types[$mime];
    $dir  = APP_ROOT . '/public/images';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Dossier images introuvable.');
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException("Impossible d'enregistrer l'image.");
    return $name;
}
