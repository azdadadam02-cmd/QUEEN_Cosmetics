<?php defined('APP_ROOT') || exit; ?>
<section class="page-head">
    <span class="eyebrow">Administration</span>
    <h1 class="h-serif">Tableau de bord</h1>
    <p class="lead">Vue d'ensemble de la boutique. Les produits et les commandes sont en consultation ; les comptes utilisateurs se gèrent ici.</p>
</section>

<div class="stats">
    <a class="stat gold" href="<?= e(url('admin_finances')) ?>">
        <span class="stat-label">Argent encaissé</span>
        <strong><?= e(money($fin['encaisse'])) ?></strong>
        <small><?= e(money($fin['a_encaisser'])) ?> à encaisser à la livraison</small>
    </a>
    <a class="stat" href="<?= e(url('admin_produits')) ?>">
        <span class="stat-label">Produits</span>
        <strong><?= (int)$produits['total'] ?></strong>
        <small><?= (int)$produits['unites'] ?> unités en stock</small>
    </a>
    <a class="stat warn" href="<?= e(url('admin_produits')) ?>">
        <span class="stat-label">Stock faible / rupture</span>
        <strong><?= (int)$produits['faible'] ?> / <?= (int)$produits['rupture'] ?></strong>
        <small>≤ 5 unités · épuisés</small>
    </a>
    <a class="stat" href="<?= e(url('admin_commandes')) ?>">
        <span class="stat-label">Commandes</span>
        <strong><?= (int)$cmdTotal ?></strong>
        <small><?= (int)($cmd['En attente'] ?? 0) ?> en attente</small>
    </a>
    <a class="stat" href="<?= e(url('admin_utilisateurs')) ?>">
        <span class="stat-label">Utilisateurs</span>
        <strong><?= (int)$usersTotal ?></strong>
        <small><?= (int)($roles['client'] ?? 0) ?> clients · <?= (int)(($roles['responsable_produits'] ?? 0) + ($roles['responsable_commandes'] ?? 0)) ?> responsables</small>
    </a>
</div>

<div class="shortcuts">
    <a class="short" href="<?= e(url('admin_produits')) ?>"><b>Produits &amp; stock</b><span>Consulter le catalogue et les quantités →</span></a>
    <a class="short" href="<?= e(url('admin_commandes')) ?>"><b>Commandes</b><span>Consulter toutes les commandes →</span></a>
    <a class="short" href="<?= e(url('admin_finances')) ?>"><b>Finances</b><span>Voir l'argent encaissé et à encaisser →</span></a>
    <a class="short" href="<?= e(url('admin_publicites')) ?>"><b>Publicités</b><span>Gérer le bandeau de l'accueil client →</span></a>
    <a class="short" href="<?= e(url('admin_utilisateurs')) ?>"><b>Utilisateurs</b><span>Ajouter, modifier, supprimer des comptes →</span></a>
</div>
