<?php defined('APP_ROOT') || exit; ?>
<section class="page-head">
    <span class="eyebrow">Consultation</span>
    <h1 class="h-serif">Commandes</h1>
    <p class="lead">Lecture seule — l'acceptation et l'annulation sont gérées par le responsable des commandes.</p>
</section>
<?php
$actions = false; $route = 'admin_commandes';
require APP_ROOT . '/app/views/partials/commandes_table.php';
