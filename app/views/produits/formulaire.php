<?php
/**
 * app/views/produits/formulaire.php
 * ROLE    : UN SEUL formulaire utilisé pour AJOUTER et pour MODIFIER un produit.
 * POURQUOI : ajout et modification demandent les mêmes champs (nom, catégorie, prix, image).
 *            Au lieu de copier deux fichiers presque identiques, la variable $edition
 *            (true/false) change juste le texte, l'adresse d'envoi et le champ « quantité ».
 *
 * Variables reçues de ProduitController : $titre, $produit (tableau, vide si ajout), $edition
 */

// Adresse où le formulaire est envoyé : modifier (avec l'id) ou créer.
$action = $edition
    ? url('produits', 'modifier', ['id' => $produit['id']])
    : url('produits', 'creer');
?>
<section class="card narrow">
  <h1><?= e($titre) ?></h1>

  <?php /* enctype="multipart/form-data" est OBLIGATOIRE pour envoyer un fichier (l'image).
           Sans lui, $_FILES reste vide. */ ?>
  <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label>Nom
      <input type="text" name="nom" maxlength="100" required
             value="<?= e($produit['nom'] ?? '') ?>">
    </label>

    <label>Catégorie
      <select name="categorie" required>
        <?php foreach (Produit::CATEGORIES as $c): ?>
          <option <?= ($produit['categorie'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Prix (MAD)
      <input type="number" name="prix" step="0.01" min="0" required
             value="<?= e($produit['prix'] ?? '') ?>">
    </label>

    <?php if (!$edition): ?>
      <?php /* Quantité demandée SEULEMENT à la création. En modification, le stock se gère
               dans la page « Stock » (règle : le stock a son propre écran). */ ?>
      <label>Quantité initiale en stock
        <input type="number" name="quantite_stock" min="0" required
               value="<?= e($produit['quantite_stock'] ?? 0) ?>">
      </label>
    <?php else: ?>
      <p class="muted">
        Stock actuel : <strong><?= (int)$produit['quantite_stock'] ?></strong>
        — à modifier depuis la page <a href="<?= e(url('produits', 'stock')) ?>">Gestion du stock</a>.
      </p>
    <?php endif; ?>

    <label>Image (jpg, png, webp, gif – 2 Mo max)
      <input type="file" name="image" accept="image/*">
    </label>

    <?php /* En modification : on montre l'image actuelle. Si on n'en choisit pas de nouvelle,
             le contrôleur garde l'ancienne. */ ?>
    <?php if ($edition && ($img = image_url($produit['image']))): ?>
      <p class="muted">Image actuelle :</p>
      <img class="mini" src="<?= e($img) ?>" alt="">
    <?php endif; ?>

    <div class="actions">
      <button class="btn"><?= $edition ? 'Enregistrer les modifications' : 'Ajouter le produit' ?></button>
      <a class="btn btn-ghost" href="<?= e(url('produits', 'liste')) ?>">Annuler</a>
    </div>
  </form>
</section>