<?php defined('APP_ROOT') || exit;
/** @var array $panier */ $lignes = $panier['lignes']; ?>

<section class="page-head">
    <span class="eyebrow">Mon panier</span>
    <h1 class="h-serif">Votre sélection</h1>
</section>

<?php if (!$lignes): ?>
    <div class="empty-state">
        <div class="empty-ico">✦</div>
        <h3>Votre panier est vide</h3>
        <p>Découvrez nos maquillages, soins et parfums d'exception.</p>
        <a class="btn btn-gold" href="<?= e(url('accueil')) ?>#produits">Voir les produits</a>
    </div>
<?php else: ?>
<div class="cart-layout">

    <div class="cart-list">
        <?php foreach ($lignes as $l): $img = img_url($l['image'] ?? ''); ?>
        <article class="cart-item">
            <div class="thumb"><span class="ph"><?= e(initial($l['nom'])) ?></span>
                <?php if ($img): ?><img src="<?= e($img) ?>" alt="" data-fallback><?php endif; ?>
            </div>
            <div class="ci-info">
                <h3><?= e($l['nom']) ?></h3>
                <small><?= e($l['categorie']) ?> · <?= e(money($l['prix'])) ?> l'unité</small>
            </div>
            <form class="qty-form" method="post" action="<?= e(url('panier_modifier')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="produit_id" value="<?= (int)$l['id'] ?>">
                <div class="qty">
                    <button type="submit" name="action" value="dec" aria-label="Diminuer la quantité">−</button>
                    <span><?= (int)$l['quantite'] ?></span>
                    <button type="submit" name="action" value="inc" aria-label="Augmenter la quantité"<?= $l['quantite'] >= $l['stock'] ? ' disabled' : '' ?>>+</button>
                </div>
                <button type="submit" name="action" value="remove" class="link-remove">Retirer</button>
            </form>
            <div class="ci-total"><?= e(money($l['sous_total'])) ?></div>
        </article>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('panier_modifier')) ?>" class="clear-form">
            <?= csrf_field() ?>
            <button type="submit" name="action" value="clear" class="link-remove">Vider le panier</button>
        </form>
    </div>

    <aside class="summary">
        <h3 class="h-serif">Récapitulatif</h3>
        <dl>
            <div><dt>Articles</dt><dd><?= (int)$panier['articles'] ?></dd></div>
            <div><dt>Sous-total</dt><dd><?= e(money($panier['total'])) ?></dd></div>
            <div><dt>Paiement</dt><dd>À la livraison</dd></div>
        </dl>
        <div class="total"><span>Total</span><strong><?= e(money($panier['total'])) ?></strong></div>
        <a class="btn btn-gold btn-block" href="<?= e(url('confirmation')) ?>">Confirmer la commande</a>
        <a class="sum-link" href="<?= e(url('accueil')) ?>#produits">← Continuer mes achats</a>
    </aside>

</div>
<?php endif; ?>
