<?php defined('APP_ROOT') || exit; ?>
<section class="page-head page-head-row">
    <div>
        <span class="eyebrow">Administration</span>
        <h1 class="h-serif">Publicités</h1>
        <p class="lead">Ces offres défilent dans le bandeau de la page d'accueil des clients. Ajoutez, modifiez ou retirez-les ici.</p>
    </div>
    <button type="button" class="btn btn-gold" data-pub-add>+ Ajouter une publicité</button>
</section>

<?php if (!$pubs): ?>
    <div class="empty-state">
        <span class="empty-ico">✦</span>
        <h3>Aucune publicité</h3>
        <p>Sans publicité, l'accueil affiche un message de bienvenue par défaut.</p>
        <button type="button" class="btn btn-gold" data-pub-add>Ajouter la première</button>
    </div>
<?php else: ?>
<div class="pub-grid">
<?php foreach ($pubs as $p): $img = img_url($p['image'] ?? ''); $red = (int)($p['reduction'] ?? 0); ?>
    <article class="pub-card">
        <div class="pub-visual">
            <?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy" data-fallback><?php endif; ?>
            <div class="pub-shade"></div>
            <h3><?= e($p['titre']) ?></h3>
            <?php if ($red > 0): ?><span class="pub-badge">-<?= $red ?>%</span><?php endif; ?>
        </div>
        <div class="pub-actions">
            <span class="count">#<?= (int)$p['id'] ?> · <?= $red > 0 ? "réduction $red %" : 'sans badge' ?><?= !empty($p['produit_nom']) ? ' · produit : ' . e($p['produit_nom']) : '' ?></span>
            <span class="act">
                <button type="button" class="btn-ghost" data-pub-edit
                        data-id="<?= (int)$p['id'] ?>"
                        data-titre="<?= e($p['titre']) ?>"
                        data-reduction="<?= $red ?>"
                        data-produit="<?= (int)($p['produit_id'] ?? 0) ?>"
                        data-image="<?= e($img) ?>">Modifier</button>
                <form method="post" action="<?= e(url('admin_publicite_supprimer')) ?>" class="inline"
                      data-confirm="Supprimer la publicité « <?= e($p['titre']) ?> » ?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn-danger">Supprimer</button>
                </form>
            </span>
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php endif; ?>

<dialog id="pub-dialog" class="dialog">
    <form method="post" action="<?= e(url('admin_publicite_save')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="g_id" value="0">
        <h3 id="g_title">Ajouter une publicité</h3>
        <div class="field">
            <label for="g_titre">Titre affiché</label>
            <input id="g_titre" name="titre" type="text" maxlength="100" required placeholder="Ex : Collection Printemps">
        </div>
        <div class="field">
            <label for="g_red">Réduction (%) <small>— 0 = aucun badge</small></label>
            <input id="g_red" name="reduction" type="number" min="0" max="90" step="1" value="0">
        </div>
        <div class="field">
            <label for="g_prod">Produit en promotion <small>— le prix réduit s'applique à ce produit</small></label>
            <select id="g_prod" name="produit_id">
                <option value="0">— Aucun (simple bandeau) —</option>
                <?php foreach ($produits as $pr): ?>
                    <option value="<?= (int)$pr['id'] ?>"><?= e($pr['nom']) ?> (<?= e(money($pr['prix'])) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="g_img">Image de fond <small id="g_img_hint">(JPG, PNG, WEBP · 3 Mo max)</small></label>
            <label class="drop" for="g_img">
                <img id="g_preview" alt="" hidden>
                <span id="g_hint">Cliquez pour choisir une image</span>
            </label>
            <input id="g_img" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
        </div>
        <div class="dialog-actions">
            <button type="button" class="btn-ghost" data-dialog-close>Annuler</button>
            <button type="submit" class="btn btn-gold btn-sm">Enregistrer</button>
        </div>
    </form>
</dialog>
