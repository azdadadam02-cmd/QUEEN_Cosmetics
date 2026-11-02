<?php
defined('APP_ROOT') || exit;
/**
 * QUEEN Cosmetics — couche de sécurité
 *  · en-têtes HTTP (CSP, anti-clickjacking, no-sniff, no-cache…)
 *  · session durcie (cookie HttpOnly/SameSite, expiration, anti-vol de session)
 *  · limitation des tentatives (anti brute-force) · journal d'audit · pages d'erreur sans fuite d'info
 * Réglages : config/app.php
 */

/* ───────────── Configuration ───────────── */
function app_config(): array {
    static $c = null;
    if ($c === null) {
        $f = APP_ROOT . '/config/app.php';
        $c = is_file($f) ? (array)(require $f) : [];
        $c += [
            'debug'          => false,   // true = afficher les erreurs PHP (développement uniquement)
            'force_https'    => false,   // true en production (HTTPS)
            'idle_timeout'   => 1800,    // déconnexion après 30 min d'inactivité
            'max_lifetime'   => 28800,   // session de 8 h maximum
        ];
    }
    return $c;
}

function https_on(): bool {
    return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}
/** Adresse IP réelle de la connexion (X-Forwarded-For volontairement ignoré : falsifiable) */
function client_ip(): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}
/** Nonce CSP, différent à chaque requête (pour les rares scripts en ligne) */
function csp_nonce(): string {
    static $n = null;
    if ($n === null) { $n = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '='); }
    return $n;
}

/* ───────────── Pages d'erreur (aucune information technique) ───────────── */
function error_page(int $code, string $titre, string $msg): void {
    if (!headers_sent()) { http_response_code($code); header('Content-Type: text/html; charset=utf-8'); }
    $home = function_exists('url') ? url('accueil') : 'index.php';
    $h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . $h($titre) . ' — QUEEN</title>'
       . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#faf6ee;font:16px/1.6 Montserrat,Segoe UI,sans-serif;color:#3b2a36}'
       . 'main{max-width:460px;margin:24px;padding:40px 36px;background:#fff;border:1px solid #e8dfcd;border-radius:24px;text-align:center;box-shadow:0 22px 50px -24px rgba(59,42,54,.42)}'
       . 'b{display:block;font:700 64px/1 Georgia,serif;color:#b9a55a}h1{font:700 28px Georgia,serif;margin:10px 0}p{color:#74697a;margin:0 0 22px}'
       . 'a{display:inline-block;padding:14px 26px;border-radius:14px;background:linear-gradient(100deg,#c2b166,#8f7b32);color:#fff;text-decoration:none;font-weight:600;font-size:13px;letter-spacing:.1em;text-transform:uppercase}</style>'
       . '</head><body><main><b>' . $code . '</b><h1>' . $h($titre) . '</h1><p>' . $h($msg) . '</p><a href="' . $h($home) . '">Retour à l\'accueil</a></main></body></html>';
    exit;
}

/* ───────────── Journal d'audit (storage/logs/security.log) ───────────── */
function audit(string $event, string $detail = ''): void {
    try {
        $clean = fn(string $s): string => preg_replace('/[\x00-\x1F\x7F]+/', ' ', mb_substr($s, 0, 300, 'UTF-8'));  // anti injection de logs
        $uid   = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? '-';
        $dir   = APP_ROOT . '/storage/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0750, true); }
        $file  = $dir . '/security.log';
        if (is_file($file) && filesize($file) > 5 * 1024 * 1024) { @rename($file, $file . '.1'); }   // rotation à 5 Mo
        $line  = date('Y-m-d H:i:s') . ' | ' . client_ip() . ' | uid=' . $clean((string)$uid) . ' | ' . $clean($event) . ' | ' . $clean($detail) . "\n";
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) { /* le journal ne doit jamais casser l'application */ }
}

/* ───────────── Limitation des tentatives (anti brute-force) ───────────── */
function throttle_db(): PDO {
    static $ready = false;
    $db = db();
    if (!$ready) {
        try { $db->query('SELECT 1 FROM tentatives_connexion LIMIT 1'); }
        catch (Throwable $e) {
            $db->exec('CREATE TABLE IF NOT EXISTS tentatives_connexion (cle VARCHAR(64) NOT NULL, ip VARCHAR(45) NOT NULL, date_tentative DATETIME NOT NULL)');
            try { $db->exec('CREATE INDEX idx_tentatives ON tentatives_connexion (cle, date_tentative)'); } catch (Throwable $e2) { /* déjà là */ }
        }
        $ready = true;
    }
    return $db;
}
/** Nombre d'événements pour $cle dans les $sec dernières secondes */
function throttle_count(string $cle, int $sec): int {
    try {
        $st = throttle_db()->prepare('SELECT COUNT(*) FROM tentatives_connexion WHERE cle = ? AND date_tentative >= ?');
        $st->execute([hash('sha256', $cle), date('Y-m-d H:i:s', time() - $sec)]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { error_log('[QUEEN] throttle: ' . $e->getMessage()); return 0; }
}
function throttle_hit(string $cle): void {
    try {
        $db = throttle_db();
        $db->prepare('INSERT INTO tentatives_connexion (cle, ip, date_tentative) VALUES (?, ?, ?)')
           ->execute([hash('sha256', $cle), client_ip(), date('Y-m-d H:i:s')]);
        if (random_int(1, 50) === 1) {                       // ménage : on garde 24 h
            $db->prepare('DELETE FROM tentatives_connexion WHERE date_tentative < ?')->execute([date('Y-m-d H:i:s', time() - 86400)]);
        }
    } catch (Throwable $e) { error_log('[QUEEN] throttle: ' . $e->getMessage()); }
}
function throttle_clear(string $cle): void {
    try { throttle_db()->prepare('DELETE FROM tentatives_connexion WHERE cle = ?')->execute([hash('sha256', $cle)]); }
    catch (Throwable $e) { error_log('[QUEEN] throttle: ' . $e->getMessage()); }
}

/* ───────────── Session ───────────── */
function ua_hash(): string { return hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? '')); }

/** Ouvre une session authentifiée (anti fixation : nouvel identifiant + nouveau jeton CSRF) */
function session_login(array $u): void {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$u['id'], 'nom' => (string)$u['nom'], 'email' => (string)$u['email'], 'role' => (string)$u['role']];
    $_SESSION['_born'] = $_SESSION['_last'] = time();
    $_SESSION['_ua']   = ua_hash();
    unset($_SESSION['csrf']);
}
/** Détruit complètement la session (données + cookie) */
function session_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
}
/** Expiration par inactivité / durée maximale / changement de navigateur (vol de cookie) */
function session_guard(array $cfg): void {
    $now = time();
    if (!empty($_SESSION['user']) || !empty($_SESSION['user_id'])) {
        $why = '';
        if (isset($_SESSION['_last']) && $now - (int)$_SESSION['_last'] > $cfg['idle_timeout'])  $why = 'inactivite';
        elseif (isset($_SESSION['_born']) && $now - (int)$_SESSION['_born'] > $cfg['max_lifetime']) $why = 'duree_max';
        elseif (isset($_SESSION['_ua']) && !hash_equals((string)$_SESSION['_ua'], ua_hash()))      $why = 'navigateur_different';
        if ($why !== '') {
            audit('session_expiree', $why);
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['flash'][] = ['type' => 'info', 'msg' => $why === 'navigateur_different'
                ? 'Par sécurité, veuillez vous reconnecter.' : 'Votre session a expiré, veuillez vous reconnecter.'];
        }
    }
    $_SESSION['_last'] = $now;
    $_SESSION['_born'] = $_SESSION['_born'] ?? $now;
    $_SESSION['_ua']   = $_SESSION['_ua']   ?? ua_hash();
}

/* ───────────── Démarrage : à appeler UNE fois, avant toute sortie ───────────── */
function security_boot(): void {
    $cfg   = app_config();
    $https = https_on();

    // Erreurs : jamais affichées au visiteur en production (chemins, requêtes SQL…)
    error_reporting(E_ALL);
    ini_set('display_errors', $cfg['debug'] ? '1' : '0');
    ini_set('log_errors', '1');
    if (!$cfg['debug']) {
        set_exception_handler(function (Throwable $e): void {
            error_log('[QUEEN] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            error_page(500, 'Une erreur est survenue', 'Cette page est momentanément indisponible. Réessayez dans un instant.');
        });
    }

    // HTTPS obligatoire en production
    if ($cfg['force_https'] && !$https && PHP_SAPI !== 'cli') {
        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }

    // En-têtes de sécurité
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');   // pas de page privée dans le cache / bouton « retour » après déconnexion
    header('Pragma: no-cache');
    if ($https) { header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); }

    // Les pages d'authentification d'origine utilisent des onclick="" en ligne → autorisés uniquement sur ces 2 pages
    $act      = $_GET['action'] ?? '';
    $authPage = is_string($act) && in_array($act, ['login', 'register'], true);
    $csp = "default-src 'self'; "
         . "script-src 'self' 'nonce-" . csp_nonce() . "'; "
         . "script-src-attr " . ($authPage ? "'unsafe-inline'" : "'none'") . "; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com data:; "
         . "img-src 'self' data: blob:; "
         . "connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'";
    header('Content-Security-Policy: ' . $csp);

    // Session durcie
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');      // refuse les identifiants de session inventés
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');        // jamais d'ID de session dans l'URL
        session_name('QUEENSESSID');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
    session_guard($cfg);
}
