<?php
/**
 * app/views/produits/liste.php
 * ROLE    : tableau des produits (consultation) + actions Modifier / Supprimer.
 * POURQUOI : la vue ne fait QUE de l'affichage ; elle reçoit $produits du contrôleur.
 *
 * Variables reçues de ProduitController::liste() : $titre, $produits
 */

// Petites statistiques calculées pour les cartes du haut (affichage seulement, pas de SQL ici).
$total  = count($produits);
$faible = count(array_filter($produits, fn($p) => $p['quantite_stock'] > 0
                                              && $p['quantite_stock'] < Produit::SEUIL_STOCK_FAIBLE));
$rupture = count(array_filter($produits, fn($p) => $p['quantite_stock'] == 0));
?>
<link rel="stylesheet" href="<?= e(asset('css/produits.css')) ?>">
<div class="produits-page">

<h1>Gestion des produits</h1>

<div class="stats">
  <div class="stat"><span><?= $total ?></span>Produits</div>
  <div class="stat"><span><?= $faible ?></span>Stock faible</div>
  <div class="stat"><span><?= $rupture ?></span>En rupture</div>
</div>

<div class="page-head">
  <h2>Liste des produits</h2>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(url('produits', 'stock')) ?>">Gérer le stock</a>
    <a class="btn" href="<?= e(url('produits', 'creer')) ?>">+ Ajouter</a>
  </div>
</div>

<?php if (!$produits): ?>
  <p class="muted">Aucun produit pour le moment.</p>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($produits as $p): ?>
      <tr>
        <td>
          <?php if ($img = image_url($p['image'])): ?>
            <img class="mini" src="<?= e($img) ?>" alt="">
          <?php else: ?>–<?php endif; ?>
        </td>
        <td><?= e($p['nom']) ?></td>
        <td><?= e($p['categorie']) ?></td>
        <td><?= money($p['prix']) ?></td>
        <td><?= (int)$p['quantite_stock'] ?></td>
        <td class="nowrap">
          <?php /* Icône crayon = lien vers la modification. title/aria-label = accessibilité. */ ?>
          <a class="icon-btn icon-edit" title="Modifier" aria-label="Modifier"
             href="<?= e(url('produits', 'modifier', ['id' => $p['id']])) ?>">
            <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
          </a>

          <?php /* Icône corbeille = bouton dans un formulaire POST (pas un lien) :
                   une suppression ne doit jamais se faire par simple clic sur une URL. */ ?>
          <form method="post" action="<?= e(url('produits', 'supprimer')) ?>"
                class="inline" data-confirm="Supprimer ce produit ?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="icon-btn icon-del" title="Supprimer" aria-label="Supprimer">
              <svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 14h10l1-14"/><path d="M10 11v5M14 11v5"/></svg>
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
</div>     