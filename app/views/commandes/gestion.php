<?php defined('APP_ROOT') || exit; ?>
<section class="page-head">
    <span class="eyebrow">Responsable des commandes</span>
    <h1 class="h-serif">Gestion des commandes</h1>
    <p class="lead">Acceptez ou annulez les commandes, puis marquez-les livrées. Un refus remet automatiquement le stock en rayon.</p>
</section>
<?php
$actions = true; $route = 'commandes_gestion';
require APP_ROOT . '/app/views/partials/commandes_table.php';
