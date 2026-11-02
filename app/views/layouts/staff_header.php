<?php defined('APP_ROOT') || exit;
/** Layout de l'espace personnel (admin / responsables) — fourni par staff_render() */
$title  = $title  ?? 'Espace';
$active = $active ?? '';
$role   = $user['role'] ?? '';
$ico = [
    'grid'  => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
    'box'   => '<path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
    'bag'   => '<path d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8L5 8Z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>',
    'coin'  => '<circle cx="12" cy="12" r="8.5"/><path d="M14.8 9.4c-.5-.9-1.5-1.4-2.8-1.4-1.6 0-2.7.8-2.7 2 0 2.9 5.6 1.4 5.6 4.1 0 1.2-1.2 2-2.9 2-1.4 0-2.5-.6-3-1.6M12 6.5V8m0 8v1.5"/>',
    'mega'  => '<path d="M3 10v4a1 1 0 0 0 1 1h2.5l7 4V5l-7 4H4a1 1 0 0 0-1 1Z"/><path d="M17.5 9a4 4 0 0 1 0 6"/>',
    'users' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/>',
];
$menus = [
    'admin' => [
        ['admin_dashboard',  'Accueil',          'grid'],
        ['admin_produits',   'Produits',         'box'],
        ['admin_commandes',  'Commandes',        'bag'],
        ['admin_finances',   'Finances',         'coin'],
        ['admin_publicites', 'Publicités',       'mega'],
        ['admin_utilisateurs', 'Utilisateurs',   'users'],
    ],
    'responsable_produits'  => [['produits_liste',    'Produits',  'box']],
    'responsable_commandes' => [['commandes_gestion', 'Commandes', 'bag']],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — QUEEN <?= e(ROLES_LABELS[$role] ?? '') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/client.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/staff.css')) ?>">
    <script nonce="<?= e(csp_nonce()) ?>">document.documentElement.classList.add('js');</script>
</head>
<body class="staff">

<header class="nav-wrap">
    <nav class="nav" aria-label="Navigation">
        <a class="brand" href="<?= e(url(staff_home($role))) ?>" aria-label="QUEEN">
            <img src="<?= e(asset('images/logo.png')) ?>" alt="QUEEN Beauty &amp; Cosmetics">
        </a>

        <ul class="nav-main">
        <?php foreach ($menus[$role] ?? [] as [$route, $label, $ic]): ?>
            <li>
                <a class="nav-link<?= $active === $route ? ' active' : '' ?>" href="<?= e(url($route)) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><?= $ico[$ic] ?></svg>
                    <span><?= e($label) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>

        <div class="nav-secondary">
            <a class="lang-switch" href="<?= e(lang_switch_url()) ?>" hreflang="<?= is_rtl() ? 'fr' : 'ar' ?>"><?= e(lang_switch_label()) ?></a>
            <span class="role-tag"><?= e(ROLES_LABELS[$role] ?? '') ?></span>
            <div class="profile" id="profile">
                <button type="button" class="profile-btn" id="profile-btn" aria-haspopup="true" aria-expanded="false">
                    <span class="avatar"><?= e(initial($user['nom'] ?: $user['email'])) ?></span>
                    <span class="profile-email"><?= e($user['nom'] ?: $user['email']) ?></span>
                    <svg class="chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="profile-menu" id="profile-menu" role="menu">
                    <div class="pm-head">
                        <span class="avatar lg"><?= e(initial($user['nom'] ?: $user['email'])) ?></span>
                        <div>
                            <strong><?= e($user['nom']) ?></strong>
                            <small><?= e($user['email']) ?></small>
                        </div>
                    </div>
                    <form method="post" action="<?= e(url('logout')) ?>" class="pm-form"><?= csrf_field() ?><button type="submit" class="pm-item danger" role="menuitem">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                        Déconnexion
                    </button></form>
                </div>
            </div>
        </div>
    </nav>
</header>

<div class="toasts" id="toasts" aria-live="polite">
<?php foreach (flash_pull() as $f): ?>
    <div class="toast toast-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
</div>

<main class="container">
