<?php
/**
 * app/views/produits/liste.php
 * ROLE    : afficher le tableau des produits (consultation) avec les boutons Modifier / Supprimer.
 * POURQUOI : la vue ne fait QUE de l'affichage. Elle reçoit $produits du contrôleur
 *            et n'appelle jamais la base de données directement.
 *
 * Variables reçues de ProduitController::liste() : $titre, $produits
 * Fonctions utilisées (définies dans public/index.php) :
 *   e()          -> htmlspecialchars : empêche l'injection de HTML/JS (faille XSS)
 *   url()        -> construit les liens du site
 *   money()      -> formate un prix (ex : 80,00 MAD)
 *   image_url()  -> adresse de l'image du produit
 *   csrf_field() -> champ caché de sécurité dans chaque formulaire POST
 */
?>
<div class="page-head">
  <h1>Liste des produits</h1>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(url('produits', 'stock')) ?>">Gérer le stock</a>
    <a class="btn" href="<?= e(url('produits', 'creer')) ?>">+ Ajouter un produit</a>
  </div>
</div>

<?php if (!$produits): ?>
  <p class="muted">Aucun produit pour le moment.</p>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($produits as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
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
          <a class="btn btn-ghost btn-sm"
             href="<?= e(url('produits', 'modifier', ['id' => $p['id']])) ?>">Modifier</a>

          <?php /* Suppression en POST (pas en lien) : un lien peut être cliqué par erreur
                   ou déclenché par un robot. data-confirm = message « Êtes-vous sûr ? » (script.js) */ ?>
          <form method="post" action="<?= e(url('produits', 'supprimer')) ?>"
                class="inline" data-confirm="Supprimer ce produit ?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-danger btn-sm">Supprimer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>