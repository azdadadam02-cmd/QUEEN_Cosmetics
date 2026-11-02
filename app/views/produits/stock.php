<?php
/**
 * app/views/produits/stock.php
 * ROLE    : page de GESTION DU STOCK du responsable produits.
 *           - voir la quantité de chaque produit
 *           - recevoir l'alerte quand le stock est faible (< 5)
 *           - AJOUTER une quantité ou FIXER la quantité d'un produit
 * POURQUOI : le stock a sa propre page (structure du projet) : la fiche produit (formulaire.php)
 *            ne s'occupe que du nom / prix / image, et ici on ne touche qu'aux quantités.
 *
 * Variables reçues de ProduitController::stock() : $titre, $produits, $alertes
 */
?>
<div class="page-head">
  <h1>Gestion du stock</h1>
  <a class="btn btn-ghost" href="<?= e(url('produits', 'liste')) ?>">← Retour aux produits</a>
</div>

<?php /* ALERTE : règle du cahier des charges « afficher une alerte lorsque le stock est
         faible (moins de 5 unités) ». $alertes vient de Produit::lowStock(). */ ?>
<?php if ($alertes): ?>
  <div class="alert alert-warning" role="alert">
    <strong>⚠ Stock faible (moins de <?= Produit::SEUIL_STOCK_FAIBLE ?> unités) :</strong>
    <?= e(implode(', ', array_map(
        fn($p) => $p['nom'] . ' (' . $p['quantite_stock'] . ')',
        $alertes
    ))) ?>
  </div>
<?php endif; ?>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>ID</th><th>Produit</th><th>Catégorie</th><th>Stock actuel</th><th>Modifier le stock</th></tr>
    </thead>
    <tbody>
    <?php foreach ($produits as $p):
        $faible = $p['quantite_stock'] < Produit::SEUIL_STOCK_FAIBLE; ?>
      <?php /* La ligne est colorée (classe « low ») si le stock est faible : repérage visuel immédiat. */ ?>
      <tr class="<?= $faible ? 'low' : '' ?>">
        <td><?= (int)$p['id'] ?></td>
        <td><?= e($p['nom']) ?></td>
        <td><?= e($p['categorie']) ?></td>
        <td>
          <strong><?= (int)$p['quantite_stock'] ?></strong>
          <?php if ($p['quantite_stock'] == 0): ?>
            <span class="status s-refusee">Rupture</span>
          <?php elseif ($faible): ?>
            <span class="status s-refusee">Faible</span>
          <?php endif; ?>
        </td>
        <td>
          <?php /* UN formulaire, DEUX boutons : name="mode" value="ajouter" ou "fixer".
                   Le contrôleur lit $_POST['mode'] pour savoir quelle fonction du modèle appeler
                   (Produit::addStock ou Produit::setStock). */ ?>
          <form method="post" action="<?= e(url('produits', 'stock')) ?>" class="inline stock-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="number" name="quantite" min="0" required class="qty" aria-label="Quantité">
            <button name="mode" value="ajouter" class="btn btn-sm">+ Ajouter</button>
            <button name="mode" value="fixer" class="btn btn-ghost btn-sm">Fixer à</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<p class="muted">
  <strong>+ Ajouter</strong> : ajoute la quantité au stock actuel (ex : réception de marchandise).<br>
  <strong>Fixer à</strong> : remplace le stock par la valeur saisie (ex : après un inventaire).
</p>