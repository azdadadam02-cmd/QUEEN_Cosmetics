<?php defined('APP_ROOT') || exit;
/** @var array $resume, $jours, $paiements @var string $filtre */
$max = max(array_column($jours, 'montant') ?: [0]);
$totalJours = array_sum(array_column($jours, 'montant'));
$filtres = ['' => 'Tous', 'paye' => 'Payés', 'encaisser' => 'À encaisser'];
$jourCourt = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer','Thu'=>'Jeu','Fri'=>'Ven','Sat'=>'Sam','Sun'=>'Dim'];
?>
<section class="page-head">
    <span class="eyebrow">Administration</span>
    <h1 class="h-serif">Finances</h1>
    <p class="lead">L'argent n'est encaissé qu'à la livraison : un paiement passe à « Payé » quand le responsable marque la commande livrée.</p>
</section>

<div class="stats">
    <div class="stat gold">
        <span class="stat-label">Total encaissé</span>
        <strong><?= e(money($resume['encaisse'])) ?></strong>
        <small><?= (int)$resume['nb_payes'] ?> commande<?= $resume['nb_payes'] > 1 ? 's' : '' ?> payée<?= $resume['nb_payes'] > 1 ? 's' : '' ?> · panier moyen <?= e(money($resume['panier_moyen'])) ?></small>
    </div>
    <div class="stat">
        <span class="stat-label">Encaissé ce mois</span>
        <strong><?= e(money($resume['mois'])) ?></strong>
        <small><?= e(date('m/Y')) ?></small>
    </div>
    <div class="stat">
        <span class="stat-label">À encaisser</span>
        <strong><?= e(money($resume['a_encaisser'])) ?></strong>
        <small>commandes acceptées, pas encore livrées</small>
    </div>
    <div class="stat">
        <span class="stat-label">En attente de validation</span>
        <strong><?= e(money($resume['en_attente'])) ?></strong>
        <small>commandes pas encore acceptées</small>
    </div>
    <div class="stat warn">
        <span class="stat-label">Refusé / annulé</span>
        <strong><?= e(money($resume['refuse'])) ?></strong>
        <small>non encaissé (stock remis)</small>
    </div>
</div>

<section class="panel chart-panel">
    <div class="chart-head">
        <h3>Encaissements — 14 derniers jours</h3>
        <span class="count"><?= e(money($totalJours)) ?></span>
    </div>
    <div class="bars" role="img" aria-label="Encaissements par jour sur 14 jours">
    <?php foreach ($jours as $j):
        $h = $max > 0 ? max(2, round($j['montant'] / $max * 100)) : 2;
        $d = strtotime($j['jour']); ?>
        <div class="bar<?= $j['montant'] > 0 ? ' has' : '' ?>" title="<?= e(date('d/m/Y', $d)) ?> : <?= e(money($j['montant'])) ?> (<?= (int)$j['nb'] ?> commande<?= $j['nb'] > 1 ? 's' : '' ?>)">
            <span class="bar-val"><?= $j['montant'] > 0 ? e(number_format($j['montant'], 0, ',', ' ')) : '' ?></span>
            <span class="bar-fill" style="height:<?= (int)$h ?>%"></span>
            <span class="bar-day"><?= e($jourCourt[date('D', $d)] ?? '') ?><br><small><?= e(date('d/m', $d)) ?></small></span>
        </div>
    <?php endforeach; ?>
    </div>
</section>

<div class="chips">
<?php foreach ($filtres as $val => $lib): ?>
    <a class="chip<?= $filtre === $val ? ' active' : '' ?>" href="<?= e(url('admin_finances', $val === '' ? [] : ['filtre' => $val])) ?>"><?= e($lib) ?></a>
<?php endforeach; ?>
</div>

<div class="panel tablewrap">
    <div class="toolbar">
        <label class="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Rechercher (n°, client…)" data-filter="#tbl-paiements" autocomplete="off">
        </label>
        <span class="count"><?= count($paiements) ?> paiement<?= count($paiements) > 1 ? 's' : '' ?></span>
    </div>
    <table class="tbl" id="tbl-paiements">
        <thead><tr><th>Commande</th><th>Client</th><th>Date</th><th class="num">Montant</th><th>Commande</th><th>Paiement</th></tr></thead>
        <tbody>
        <?php foreach ($paiements as $p):
            [$lib, $cls] = statut_info($p['statut']);
            $paye = $p['paiement_statut'] === 'Paye'; ?>
            <tr>
                <td><b>#<?= (int)$p['commande_id'] ?></b></td>
                <td><strong><?= e($p['client_nom'] ?: 'Client supprimé') ?></strong><small><?= e($p['client_email'] ?? '') ?></small></td>
                <td><?= e(date('d/m/Y H:i', strtotime($p['date_commande']))) ?></td>
                <td class="num"><b><?= e(money($p['montant'])) ?></b></td>
                <td><span class="pill pill-<?= e($cls) ?>"><?= e($lib) ?></span></td>
                <td>
                    <?php if ($paye): ?><span class="pill pill-livree">Payé</span>
                    <?php elseif ($p['statut'] === 'Refusee'): ?><span class="pill pill-refusee">Annulé</span>
                    <?php else: ?><span class="pill pill-attente">À encaisser</span><?php endif; ?>
                    <small><?= e($p['methode'] ?? 'Livraison') ?></small>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$paiements): ?><tr><td colspan="6" class="empty-row">Aucun paiement pour ce filtre.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
