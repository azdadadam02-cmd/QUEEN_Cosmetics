<?php
/**
 * app/views/layouts/staff_header.php
 * En-tete des pages du personnel (responsables + admin).
 * Les fichiers header.php / footer.php restent libres pour l'espace client.
 */
$user    = current_user();
$role    = $user['role'] ?? '';
$flashes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);   // un message ne s'affiche qu'une fois

$roles = [
    'admin'                 => 'Administrateur',
    'responsable_produits'  => 'Resp. produits & stock',
    'responsable_commandes' => 'Resp. commandes',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titre ?? 'Espace') ?> | QUEEN Cosmetics</title>
  <link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<header class="topbar">
  <img class="brand-logo" src="<?= e(asset('images/logo.png')) ?>" alt="QUEEN Cosmetics">
  <nav>
    <?php if ($role === 'responsable_produits'): ?>
      <a href="<?= e(url('produits', 'liste')) ?>">Produits</a>
      <a href="<?= e(url('produits', 'stock')) ?>">Stock</a>
    <?php endif; ?>
    <?php if ($role === 'responsable_commandes' || $role === 'admin'): ?>
      <a href="<?= e(url('commandes', 'gestion')) ?>">Commandes</a>
      <a href="<?= e(url('paiements', 'gestion')) ?>">Paiements</a>
    <?php endif; ?>
  </nav>
  <div class="who">
    <?= e($user['nom'] ?? '') ?> <span class="role"><?= e($roles[$role] ?? $role) ?></span>
    <a class="logout" href="index.php?action=logout">Déconnexion</a>
  </div>
</header>

<main class="container">
<?php foreach ($flashes as $f): ?>
  <div class="flash flash-<?= e($f['type']) ?>" role="alert"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
