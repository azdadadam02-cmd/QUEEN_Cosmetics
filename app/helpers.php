<?php
/**
 * app/helpers.php
 * ROLE : petites fonctions utilisees par les controleurs et les vues
 *        (affichage securise, URL, messages, CSRF, roles, upload d'image).
 */

define('UPLOAD_DIR', dirname(__DIR__) . '/public/uploads');

/* ------------------------------ Affichage ------------------------------ */

/** Echappe le HTML (protection XSS). A utiliser sur TOUT ce qui vient de la base ou de l'utilisateur. */
function e($valeur): string
{
    return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
}

/** Prix : 1234.5 -> « 1 234,50 MAD » */
function money($montant): string
{
    return number_format((float)$montant, 2, ',', ' ') . ' MAD';
}

/** Chemin d'un fichier public (css, js, images). Relatif a public/index.php. */
function asset(string $chemin): string
{
    return ltrim($chemin, '/');
}

/** URL d'une action : url('produits', 'modifier', ['id' => 3]) */
function url(string $controller, string $action = '', array $params = []): string
{
    return 'index.php?' . http_build_query(array_merge(
        ['controller' => $controller, 'action' => $action],
        $params
    ));
}

/** Redirection vers une action puis arret du script. */
function redirect(string $controller, string $action = '', array $params = []): void
{
    header('Location: ' . url($controller, $action, $params));
    exit;
}

/* ------------------------------ Messages flash ------------------------------ */

/** Message affiche une seule fois a la page suivante. $type = 'success' ou 'error'. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

/* ------------------------------ Utilisateur / roles ------------------------------ */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Laisse passer seulement les roles indiques ; sinon redirige vers le login (ou 403). */
function require_role(string ...$roles): void
{
    $user = current_user();
    if (!$user) {
        header('Location: index.php?action=login');
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><meta charset="UTF-8"><title>Accès refusé</title>'
           . '<body style="font-family:sans-serif;text-align:center;padding:60px">'
           . '<h1>403 — Accès refusé</h1><p>Votre rôle ne permet pas d\'ouvrir cette page.</p>'
           . '<p><a href="index.php?action=logout">Se déconnecter</a></p></body>';
        exit;
    }
}

/* ------------------------------ CSRF ------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Refuse toute requete POST sans le bon jeton (et donc aussi tout appel par simple lien GET). */
function verify_csrf(): void
{
    $envoye = $_POST['csrf_token'] ?? '';
    if (!is_string($envoye) || !hash_equals($_SESSION['csrf'] ?? '', $envoye)) {
        http_response_code(419);
        exit('Requête refusée (jeton CSRF invalide). Revenez en arrière et réessayez.');
    }
}

/* ------------------------------ Images ------------------------------ */

/** URL publique de l'image d'un produit, ou null si pas d'image / fichier absent. */
function image_url(?string $nom): ?string
{
    if (!$nom) return null;
    $fichier = basename($nom);
    if (!is_file(UPLOAD_DIR . '/' . $fichier)) return null;
    return 'uploads/' . rawurlencode($fichier);
}

/**
 * Enregistre l'image envoyee par le champ $champ dans public/uploads/.
 * Retourne le nom du fichier, ou null si aucun fichier n'a ete envoye.
 * Leve une RuntimeException (message affichable) si le fichier est refuse.
 */
function upload_image(string $champ): ?string
{
    if (empty($_FILES[$champ]) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$champ];

    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Image trop lourde (2 Mo maximum).');
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Échec de l'envoi de l'image.");
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Image trop lourde (2 Mo maximum).');
    }

    // On verifie le VRAI type du fichier (pas seulement son extension).
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Format non autorisé (jpg, png, webp ou gif uniquement).');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException("Impossible de créer le dossier d'images.");
    }

    $nom = bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $nom)) {
        throw new RuntimeException("Impossible d'enregistrer l'image.");
    }
    return $nom;
}

function delete_image(?string $nom): void
{
    if (!$nom) return;
    $chemin = UPLOAD_DIR . '/' . basename($nom);
    if (is_file($chemin)) {
        @unlink($chemin);
    }
}

/* ------------------------------ Vues ------------------------------ */

/** Affiche une vue dans le layout du personnel (header + vue + footer). */
function render(string $__vue, array $__donnees = []): void
{
    extract($__donnees, EXTR_SKIP);   // chaque cle du tableau devient une variable ($produits, $titre...)
    require __DIR__ . '/views/layouts/staff_header.php';
    require __DIR__ . '/views/' . $__vue . '.php';
    require __DIR__ . '/views/layouts/staff_footer.php';
}
