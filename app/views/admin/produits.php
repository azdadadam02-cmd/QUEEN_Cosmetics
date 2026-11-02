<?php defined('APP_ROOT') || exit; ?>
<section class="page-head">
    <span class="eyebrow">Consultation</span>
    <h1 class="h-serif">Produits &amp; stock</h1>
    <p class="lead"><?= (int)$stats['total'] ?> produits · <?= (int)$stats['unites'] ?> unités · <?= (int)$stats['faible'] ?> en stock faible · <?= (int)$stats['rupture'] ?> en rupture.</p>
</section>
<?php require APP_ROOT . '/app/views/partials/produits_table.php'; ?>
