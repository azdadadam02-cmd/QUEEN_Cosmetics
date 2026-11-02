<?php defined('APP_ROOT') || exit;
/**
 * @var array $commandes
 * @var bool  $actions   true = boutons Accepter / Refuser / Livrée (responsable) ; false = lecture seule (admin)
 * @var array $compteurs statut => nombre
 * @var string $filtre
 * @var string $route    route de la page (pour les filtres)
 */
if (!function_exists('partial_statut_btn')) {
    function partial_statut_btn(int $id, string $statut, string $label, string $cls, string $confirm = ''): string {
        return '<form method="post" action="' . e(url('commande_statut')) . '" class="inline"'
             . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
             . csrf_field()
             . '<input type="hidden" name="id" value="' . $id . '">'
             . '<input type="hidden" name="statut" value="' . e($statut) . '">'
             . '<button type="submit" class="' . e($cls) . '">' . e($label) . '</button></form>';
    }
}
$filtres = ['' => 'Toutes', 'En attente' => 'En attente', 'Acceptee' => 'Acceptées', 'Livree' => 'Livrées', 'Refusee' => 'Refusées'];
?>
<div class="chips">
<?php foreach ($filtres as $val => $lib):
    $n = $val === '' ? array_sum($compteurs) : ($compteurs[$val] ?? 0); ?>
    <a class="chip<?= $filtre === $val ? ' active' : '' ?>" href="<?= e(url($route, $val === '' ? [] : ['statut' => $val])) ?>"><?= e($lib) ?> <b>(<?= (int)$n ?>)</b></a>
<?php endforeach; ?>
</div>

<div class="panel tablewrap">
    <div class="toolbar">
        <label class="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Rechercher (n°, client…)" data-filter="#tbl-commandes" autocomplete="off">
        </label>
        <span class="count"><?= count($commandes) ?> commande<?= count($commandes) > 1 ? 's' : '' ?></span>
    </div>
    <table class="tbl" id="tbl-commandes">
        <thead><tr><th>N°</th><th>Client</th><th>Date</th><th class="num">Total</th><th>Statut</th><th>Paiement</th><th class="right"><?= $actions ? 'Actions' : '' ?></th></tr></thead>
        <tbody>
        <?php foreach ($commandes as $c):
            [$lib, $cls] = statut_info($c['statut']);
            $pay = ($c['paiement_statut'] ?? '') === 'Paye'; ?>
            <tr class="o-row">
                <td><b>#<?= (int)$c['id'] ?></b></td>
                <td><strong><?= e($c['client_nom'] ?: 'Client supprimé') ?></strong><small><?= e($c['client_email'] ?? '') ?></small><?php if (!empty($c['telephone'])): ?><small>☎ <?= e($c['telephone']) ?></small><?php endif; ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($c['date_commande']))) ?></td>
                <td class="num"><b><?= e(money($c['total'])) ?></b></td>
                <td><span class="pill pill-<?= e($cls) ?>"><?= e($lib) ?></span></td>
                <td><span class="pay-state<?= $pay ? ' paid' : '' ?>"><?= $pay ? 'Payé' : 'À encaisser' ?></span><small><?= e($c['methode'] ?? 'Livraison') ?></small></td>
                <td class="right act">
                <?php if ($actions): ?>
                    <?php if ($c['statut'] === 'En attente'): ?>
                        <?= partial_statut_btn($c['id'], 'Acceptee', 'Accepter', 'btn-ok') ?>
                        <?= partial_statut_btn($c['id'], 'Refusee', 'Refuser', 'btn-danger', 'Refuser la commande n° ' . (int)$c['id'] . ' ? Le stock sera remis en rayon.') ?>
                    <?php elseif ($c['statut'] === 'Acceptee'): ?>
                        <?= partial_statut_btn($c['id'], 'Livree', 'Marquer livrée', 'btn-ok', 'Confirmer la livraison et l\'encaissement de la commande n° ' . (int)$c['id'] . ' ?') ?>
                        <?= partial_statut_btn($c['id'], 'Refusee', 'Annuler', 'btn-danger', 'Annuler la commande n° ' . (int)$c['id'] . ' ? Le stock sera remis en rayon.') ?>
                    <?php endif; ?>
                <?php endif; ?>
                    <button type="button" class="btn-ghost" data-toggle aria-expanded="false">Détails</button>
                </td>
            </tr>
            <tr class="o-detail" hidden>
                <td colspan="7">
                    <?php if (!empty($c['adresse']) || !empty($c['telephone'])): ?>
                    <div class="ship">
                        <b>Livraison</b>
                        <span><?= e($c['nom_livraison'] ?: $c['client_nom']) ?> · ☎ <?= e($c['telephone'] ?? '—') ?></span>
                        <span><?= e(trim(($c['adresse'] ?? '') . ', ' . ($c['ville'] ?? ''), ' ,')) ?></span>
                    </div>
                    <?php endif; ?>
                    <ul class="lines">
                    <?php foreach ($c['articles'] as $a): ?>
                        <li>
                            <span class="thumb xs"><span class="ph"><?= e(initial($a['nom'])) ?></span>
                                <?php if (!empty($a['image'])): ?><img src="<?= e(img_url($a['image'])) ?>" alt="" loading="lazy" data-fallback><?php endif; ?>
                            </span>
                            <span><?= e($a['nom']) ?> <em>× <?= (int)$a['quantite'] ?></em></span>
                            <b><?= e(money($a['prix_unitaire'] * $a['quantite'])) ?></b>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$commandes): ?><tr><td colspan="7" class="empty-row">Aucune commande<?= $filtre ? ' avec ce statut' : '' ?>.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
