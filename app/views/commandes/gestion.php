<?php
/**
 * app/views/commandes/gestion.php
 * ROLE    : afficher toutes les commandes avec leurs produits, et les boutons
 *           Accepter / Refuser / Marquer livrée selon le statut.
 * POURQUOI : la vue ne fait QUE de l'affichage. Les décisions (stock, statuts) sont dans
 *            le modèle Commande ; ici les boutons ne sont qu'une aide visuelle.
 *
 * Variables reçues de CommandeController::gestion() :
 *   $commandes     = liste des commandes (avec client_nom, paiement_statut...)
 *   $details       = [ id_commande => [lignes de produits] ]
 *   $lecture_seule = true si c'est l'admin (il consulte, il n'agit pas)
 */

// Libellés lisibles : la base stocke « Acceptee », on affiche « Acceptée ».
$libelles = ['En attente' => 'En attente', 'Acceptee' => 'Acceptée', 'Refusee' => 'Refusée', 'Livree' => 'Livrée',
             'Non paye' => 'Non payé', 'Paye' => 'Payé'];

// Nom de classe CSS pour la couleur du badge : « En attente » -> « en-attente »
$classe = fn(string $s) => strtolower(str_replace(' ', '-', $s));

// Petites statistiques pour les cartes du haut (affichage seulement).
$compte = array_count_values(array_column($commandes, 'statut'));
?>
<link rel="stylesheet" href="<?= e(asset('css/commandes.css')) ?>">
<div class="commandes-page">

<h1>Gestion des commandes<?= $lecture_seule ? ' <small class="muted">(lecture seule)</small>' : '' ?></h1>

<div class="stats">
  <div class="stat"><span><?= count($commandes) ?></span>Commandes</div>
  <div class="stat"><span><?= $compte['En attente'] ?? 0 ?></span>En attente</div>
  <div class="stat"><span><?= $compte['Acceptee'] ?? 0 ?></span>Acceptées</div>
  <div class="stat"><span><?= $compte['Livree'] ?? 0 ?></span>Livrées</div>
</div>

<?php if (!$commandes): ?>
  <p class="muted">Aucune commande pour le moment.</p>
<?php endif; ?>

<?php foreach ($commandes as $c):
    $lignes = $details[$c['id']] ?? [];

    // Au moins un produit dont le stock actuel est inférieur à la quantité demandée ?
    // (sert seulement à désactiver le bouton « Accepter » : le modèle revérifie de toute façon)
    $stockInsuffisant = array_filter($lignes, fn($l) => $l['quantite_stock'] < $l['quantite']);
?>
<article class="card">

  <header class="order-head">
    <strong>Commande n°<?= (int)$c['id'] ?></strong>
    <span><?= e($c['client_nom']) ?> <span class="muted">&lt;<?= e($c['client_email']) ?>&gt;</span></span>
    <span class="muted"><?= e(date('d/m/Y H:i', strtotime($c['date_commande']))) ?></span>
    <span class="status s-<?= e($classe($c['statut'])) ?>"><?= e($libelles[$c['statut']] ?? $c['statut']) ?></span>
    <span class="status s-<?= e($classe((string)$c['paiement_statut'])) ?>"><?= e($libelles[$c['paiement_statut']] ?? '—') ?></span>
  </header>

  <ul class="lines">
    <?php foreach ($lignes as $l):
        $manque = $l['quantite_stock'] < $l['quantite']; ?>
      <li>
        <?= (int)$l['quantite'] ?> × <?= e($l['nom']) ?>
        <span class="muted">(<?= money($l['prix_unitaire']) ?>)</span>
        <?php /* Le stock n'est utile à comparer que tant que la commande n'est pas traitée */ ?>
        <?php if ($c['statut'] === 'En attente'): ?>
          <span class="<?= $manque ? 'stock-ko' : 'muted' ?>">
            — stock : <?= (int)$l['quantite_stock'] ?><?= $manque ? ' (insuffisant)' : '' ?>
          </span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <footer class="order-foot">
    <strong>Total : <?= money($c['total']) ?></strong> <span class="muted">· paiement à la livraison</span>

    <?php if (!$lecture_seule): ?>
    <div class="actions">

      <?php if ($c['statut'] === 'En attente'): ?>
        <?php /* Chaque bouton est un petit formulaire POST : id de la commande + jeton CSRF */ ?>
        <form method="post" action="<?= e(url('commandes', 'accepter')) ?>" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-sm" <?= $stockInsuffisant ? 'disabled title="Stock insuffisant"' : '' ?>>Accepter</button>
        </form>
        <form method="post" action="<?= e(url('commandes', 'refuser')) ?>" class="inline"
              data-confirm="Refuser cette commande ?">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-danger btn-sm">Refuser</button>
        </form>

      <?php elseif ($c['statut'] === 'Acceptee'): ?>
        <form method="post" action="<?= e(url('commandes', 'livrer')) ?>" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-sm">Marquer livrée</button>
        </form>

      <?php elseif ($c['statut'] === 'Livree' && $c['paiement_statut'] === 'Non paye'): ?>
        <?php /* La confirmation du paiement se fait dans la page Paiements (autre fichier) */ ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('paiements', 'gestion')) ?>">Confirmer le paiement →</a>
      <?php endif; ?>

    </div>
    <?php endif; ?>
  </footer>

</article>
<?php endforeach; ?>

</div>