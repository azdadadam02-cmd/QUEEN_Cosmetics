<?php
/**
 * app/views/paiements/gestion.php
 * Variables : $titre, $paiements, $lecture_seule
 */
$libelles = ['En attente' => 'En attente', 'Acceptee' => 'Acceptée', 'Refusee' => 'Refusée', 'Livree' => 'Livrée',
             'Non paye' => 'Non payé', 'Paye' => 'Payé'];
$classe = fn(string $s) => strtolower(str_replace(' ', '-', $s));
?>
<link rel="stylesheet" href="<?= e(asset('css/commandes.css')) ?>">
<div class="commandes-page">

<h1>Paiements à la livraison<?= $lecture_seule ? ' <small class="muted">(lecture seule)</small>' : '' ?></h1>

<?php if (!$paiements): ?>
  <p class="muted">Aucun paiement pour le moment.</p>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Commande</th><th>Client</th><th>Date</th><th>Montant</th><th>Commande</th><th>Paiement</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($paiements as $p): ?>
      <tr>
        <td>n°<?= (int)$p['commande_id'] ?></td>
        <td><?= e($p['client_nom']) ?> <span class="muted">&lt;<?= e($p['client_email']) ?>&gt;</span></td>
        <td><?= e(date('d/m/Y H:i', strtotime($p['date_commande']))) ?></td>
        <td><?= money($p['montant']) ?></td>
        <td><span class="status s-<?= e($classe($p['commande_statut'])) ?>"><?= e($libelles[$p['commande_statut']] ?? $p['commande_statut']) ?></span></td>
        <td><span class="status s-<?= e($classe($p['statut'])) ?>"><?= e($libelles[$p['statut']] ?? $p['statut']) ?></span></td>
        <td class="nowrap">
          <?php if (!$lecture_seule && $p['commande_statut'] === 'Livree' && $p['statut'] === 'Non paye'): ?>
            <form method="post" action="<?= e(url('paiements', 'confirmer')) ?>" class="inline"
                  data-confirm="Confirmer que le client a payé ?">
              <?= csrf_field() ?>
              <input type="hidden" name="commande_id" value="<?= (int)$p['commande_id'] ?>">
              <button class="btn btn-sm">Confirmer le paiement</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

</div>
