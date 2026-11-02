<?php defined('APP_ROOT') || exit;
/** @var array $commandes */ ?>

<section class="page-head">
    <span class="eyebrow">Suivi</span>
    <h1 class="h-serif">Notifications</h1>
    <p class="lead">Retrouvez ici l'état de chacune de vos commandes.</p>
</section>

<?php if (!$commandes): ?>
    <div class="empty-state">
        <div class="empty-ico">✦</div>
        <h3>Aucune notification</h3>
        <p>Dès que vous passerez une commande, son suivi apparaîtra ici.</p>
        <a class="btn btn-gold" href="<?= e(url('accueil')) ?>#produits">Découvrir nos produits</a>
    </div>
<?php else: ?>
<div class="notifs">
    <?php foreach ($commandes as $c):
        [$label, $cls, $msg] = statut_info($c['statut']);
        $refus = $cls === 'refusee';
        $rank  = ['attente' => 1, 'acceptee' => 2, 'livree' => 3, 'refusee' => 2][$cls] ?? 1;
        $steps = [1 => 'Reçue', 2 => $refus ? 'Refusée' : 'Acceptée', 3 => 'Livrée'];
        $paye  = ($c['paiement_statut'] ?? '') === 'Paye';
    ?>
    <article class="notif<?= $c['nouveau'] ? ' new' : '' ?> reveal">
        <header class="notif-head">
            <div>
                <h3>Commande n° <?= (int)$c['id'] ?></h3>
                <small><?= e(date('d/m/Y à H:i', strtotime($c['date_commande']))) ?></small>
            </div>
            <div class="notif-side">
                <?php if ($c['nouveau']): ?><span class="new-tag">Nouveau</span><?php endif; ?>
                <span class="pill pill-<?= e($cls) ?>"><?= e($label) ?></span>
            </div>
        </header>

        <p class="notif-msg"><?= e($msg) ?></p>
        <?php if (!empty($c['adresse'])): ?>
        <p class="notif-ship">📍 Livraison : <?= e($c['nom_livraison'] ?: '') ?> — <?= e(trim($c['adresse'] . ', ' . ($c['ville'] ?? ''), ' ,')) ?> · ☎ <?= e($c['telephone'] ?? '') ?></p>
        <?php endif; ?>

        <ol class="steps<?= $refus ? ' refused' : '' ?>" aria-label="Progression de la commande">
            <?php foreach ($steps as $n => $txt):
                $state = $n < $rank ? 'done' : ($n === $rank ? 'current' : ''); ?>
                <li class="<?= $state ?>"><i><?= $state === 'done' ? '✓' : ($refus && $n === 2 ? '✕' : $n) ?></i><span><?= e($txt) ?></span></li>
            <?php endforeach; ?>
        </ol>

        <ul class="notif-items">
            <?php foreach ($c['articles'] as $a): $img = img_url($a['image'] ?? ''); ?>
            <li>
                <div class="thumb xs"><span class="ph"><?= e(initial($a['nom'])) ?></span>
                    <?php if ($img): ?><img src="<?= e($img) ?>" alt="" data-fallback><?php endif; ?>
                </div>
                <span class="ni-name"><?= e($a['nom']) ?> <em>× <?= (int)$a['quantite'] ?></em></span>
                <span class="ni-price"><?= e(money($a['prix_unitaire'] * $a['quantite'])) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>

        <footer class="notif-foot">
            <span class="pay-state <?= $paye ? 'paid' : '' ?>">
                <?= $paye ? 'Paiement reçu' : 'Paiement à la livraison · non réglé' ?>
            </span>
            <strong class="notif-total"><?= e(money($c['total'])) ?></strong>
        </footer>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
