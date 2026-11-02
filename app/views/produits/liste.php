<?php defined('APP_ROOT') || exit; ?>
<section class="page-head">
    <span class="eyebrow">Responsable des produits</span>
    <h1 class="h-serif">Produits</h1>
    <p class="lead">Ajoutez de nouveaux produits au catalogue : ils apparaissent aussitôt sur l'accueil des clients.</p>
</section>

<div class="split">
    <form class="panel form-panel" method="post" action="<?= e(url('produit_ajouter')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <h3>Nouveau produit</h3>

        <div class="field">
            <label for="p_nom">Nom du produit</label>
            <input id="p_nom" name="nom" type="text" maxlength="100" required placeholder="Ex : Rouge à lèvres Royal" value="<?= e($old['nom']) ?>">
        </div>
        <div class="field">
            <label for="p_cat">Catégorie</label>
            <input id="p_cat" name="categorie" type="text" maxlength="50" list="cats" placeholder="Maquillage, Soin, Parfum…" value="<?= e($old['categorie']) ?>">
            <datalist id="cats"><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="row2">
            <div class="field">
                <label for="p_prix">Prix (DH)</label>
                <input id="p_prix" name="prix" type="text" inputmode="decimal" required placeholder="120" value="<?= e($old['prix']) ?>">
            </div>
            <div class="field">
                <label for="p_stock">Stock</label>
                <input id="p_stock" name="stock" type="number" min="0" step="1" required placeholder="20" value="<?= e($old['stock']) ?>">
            </div>
        </div>
        <div class="field">
            <label for="p_promo">Promotion (%) <small>— 0 = aucune ; visible chez les clients</small></label>
            <input id="p_promo" name="promotion" type="number" min="0" max="90" step="1" placeholder="0" value="<?= e($old['promotion']) ?>">
        </div>
        <div class="field">
            <label for="p_img">Image <small>(JPG, PNG, WEBP · 3 Mo max)</small></label>
            <label class="drop" for="p_img">
                <img id="p_preview" alt="" hidden>
                <span id="p_hint">Cliquez pour choisir une image</span>
            </label>
            <input id="p_img" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
        </div>
        <button type="submit" class="btn btn-gold btn-block">Ajouter le produit</button>
    </form>

    <div>
        <?php $editable = true; require APP_ROOT . '/app/views/partials/produits_table.php'; ?>
    </div>
</div>

<dialog id="prod-dialog" class="dialog">
    <form method="post" action="<?= e(url('produit_modifier')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="e_id" value="0">
        <h3>Modifier le produit</h3>
        <div class="field">
            <label for="e_nom">Nom du produit</label>
            <input id="e_nom" name="nom" type="text" maxlength="100" required>
        </div>
        <div class="field">
            <label for="e_cat">Catégorie</label>
            <input id="e_cat" name="categorie" type="text" maxlength="50" list="cats">
        </div>
        <div class="row2">
            <div class="field">
                <label for="e_prix">Prix (DH)</label>
                <input id="e_prix" name="prix" type="text" inputmode="decimal" required>
            </div>
            <div class="field">
                <label for="e_stock">Stock</label>
                <input id="e_stock" name="stock" type="number" min="0" step="1" required>
            </div>
        </div>
        <div class="field">
            <label for="e_promo">Promotion (%) <small>— 0 = aucune</small></label>
            <input id="e_promo" name="promotion" type="number" min="0" max="90" step="1" value="0">
        </div>
        <div class="field">
            <label for="e_img">Image <small>(laisser vide pour garder l'actuelle)</small></label>
            <label class="drop" for="e_img">
                <img id="e_preview" alt="" hidden>
                <span id="e_hint">Cliquez pour changer l'image</span>
            </label>
            <input id="e_img" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
        </div>
        <div class="dialog-actions">
            <button type="button" class="btn-ghost" data-dialog-close>Annuler</button>
            <button type="submit" class="btn btn-gold btn-sm">Enregistrer</button>
        </div>
    </form>
</dialog>
