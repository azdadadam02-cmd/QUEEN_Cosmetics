<?php
/** @var array $nav  (user, cartCount, notifCount) — fourni par render() */
$title  = $title  ?? 'QUEEN';
$active = $active ?? '';
$user   = $nav['user'];
$on     = fn(string $k): string => $active === $k ? ' active' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> — QUEEN Beauty &amp; Cosmetics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/client.css')) ?>">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>

<header class="nav-wrap">
    <nav class="nav" aria-label="Navigation principale">

        <!-- ═════ LOGO ═════ -->
        <a class="brand" href="<?= e(url('accueil')) ?>" aria-label="QUEEN — Accueil">
            <img src="<?= e(asset('images/logo.png')) ?>" alt="QUEEN Beauty &amp; Cosmetics">
        </a>

        <!-- ═════ NAV PRINCIPALE : Accueil · Panier · Notifications ═════ -->
        <ul class="nav-main">
            <li>
                <a class="nav-link<?= $on('accueil') ?>" href="<?= e(url('accueil')) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5a.5.5 0 0 0 .5.5H9.5v-5.5h5V20H18a.5.5 0 0 0 .5-.5V10"/></svg>
                    <span>Accueil</span>
                </a>
            </li>
            <li>
                <a class="nav-link<?= $on('panier') ?>" href="<?= e(url('panier')) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8L5 8Z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>
                    <span>Panier</span>
                    <b class="badge" id="cart-badge"<?= $nav['cartCount'] < 1 ? ' hidden' : '' ?>><?= (int)$nav['cartCount'] ?></b>
                </a>
            </li>
            <li>
                <a class="nav-link<?= $on('notifications') ?>" href="<?= e(url('notifications')) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 16.5V11a6 6 0 1 1 12 0v5.5l1.5 2h-15L6 16.5Z"/><path d="M10 21a2.2 2.2 0 0 0 4 0"/></svg>
                    <span>Notifications</span>
                    <?php if ($nav['notifCount'] > 0): ?><b class="badge" aria-label="<?= (int)$nav['notifCount'] ?> nouvelle(s)"><?= (int)$nav['notifCount'] ?></b><?php endif; ?>
                </a>
            </li>
        </ul>

        <!-- ═════ NAV SECONDAIRE : Profil (connecté) ou Connexion ═════ -->
        <div class="nav-secondary">
        <?php if ($user): ?>
            <div class="profile" id="profile">
                <button type="button" class="profile-btn" id="profile-btn" aria-haspopup="true" aria-expanded="false">
                    <span class="avatar"><?= e(initial($user['nom'] ?: $user['email'])) ?></span>
                    <span class="profile-email"><?= e($user['email']) ?></span>
                    <svg class="chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="profile-menu" id="profile-menu" role="menu">
                    <div class="pm-head">
                        <span class="avatar lg"><?= e(initial($user['nom'] ?: $user['email'])) ?></span>
                        <div>
                            <strong><?= e($user['nom'] ?: 'Mon compte') ?></strong>
                            <small><?= e($user['email']) ?></small>
                        </div>
                    </div>
                    <a class="pm-item danger" role="menuitem" href="<?= e(url('logout')) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                        Déconnexion
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a class="btn btn-gold btn-sm" href="<?= e(url('login')) ?>">Connexion</a>
        <?php endif; ?>
        </div>

    </nav>
</header>

<div class="toasts" id="toasts" aria-live="polite">
<?php foreach (flash_pull() as $f): ?>
    <div class="toast toast-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
</div>

<main class="container">
