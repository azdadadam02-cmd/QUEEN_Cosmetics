<?php
/**
 * QUEEN Cosmetics — fonctions utilitaires partagées (espace client)
 */
if (!defined('APP_ROOT')) { define('APP_ROOT', dirname(__DIR__)); }
if (session_status() === PHP_SESSION_NONE) { session_start(); }

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
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ───────────── CSRF ───────────── */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (is_ajax()) json_out(['ok' => false, 'message' => 'Session expirée, rechargez la page.'], 419);
        flash_add('error', 'Session expirée, veuillez réessayer.');
        redirect('accueil');
    }
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
    return $pdo = $found;
}

/* ───────────── Utilisateur connecté ─────────────
 * Lit la session créée par AuthController (plusieurs noms de clés acceptés).
 */
function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;

    $s = $_SESSION['user'] ?? $_SESSION['utilisateur'] ?? null;
    if (is_array($s)) {
        $id    = $s['id'] ?? $s['user_id'] ?? null;
        $nom   = $s['nom'] ?? $s['name'] ?? '';
        $email = $s['email'] ?? '';
        $role  = $s['role'] ?? 'client';
    } else {
        $id    = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['utilisateur_id'] ?? $_SESSION['id_utilisateur'] ?? null;
        $nom   = $_SESSION['user_nom'] ?? $_SESSION['nom'] ?? $_SESSION['user_name'] ?? '';
        $email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
        $role  = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'client';
    }
    if (!$id) return $cache = null;

    if ($nom === '' || $email === '') {            // complète depuis la base si besoin
        $st = db()->prepare('SELECT nom, email, role FROM utilisateurs WHERE id = ?');
        $st->execute([(int)$id]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $nom   = $nom   !== '' ? $nom   : $row['nom'];
            $email = $email !== '' ? $email : $row['email'];
            $role  = $role ?: $row['role'];
        } else {
            return $cache = null;                  // compte supprimé
        }
    }
    return $cache = ['id' => (int)$id, 'nom' => (string)$nom, 'email' => (string)$email, 'role' => (string)$role];
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
