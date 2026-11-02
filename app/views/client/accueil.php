<?php
/** @var array $produits, $cats, $pubs */
$multi = count($pubs) > 1;
?>

<!-- ═════════ BANDEAU PUBLICITAIRE (défilement automatique) ═════════ -->
<section class="ads" id="ads" aria-roledescription="carrousel" aria-label="Offres QUEEN">
    <div class="ads-track" id="ads-track">
    <?php if ($pubs): foreach ($pubs as $p):
        $img = img_url($p['image'] ?? '');
        $red = (int)($p['reduction'] ?? 0); ?>
        <article class="ad-slide">
            <?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?>
            <div class="ad-shade"></div>
            <div class="ad-content">
                <span class="eyebrow gold">Offre exclusive</span>
                <h2><?= e($p['titre']) ?></h2>
                <a href="#produits" class="btn btn-light">Découvrir la collection</a>
            </div>
            <?php if ($red > 0): ?>
                <div class="ad-badge"><small>jusqu'à</small><strong>-<?= $red ?>%</strong></div>
            <?php endif; ?>
        </article>
    <?php endforeach; else: ?>
        <article class="ad-slide">
            <div class="ad-shade"></div>
            <div class="ad-content">
                <span class="eyebrow gold">Haute beauté &amp; parfums</span>
                <h2>L'élégance royale au cœur de votre beauté</h2>
                <a href="#produits" class="btn btn-light">Découvrir la collection</a>
            </div>
        </article>
    <?php endif; ?>
    </div>
    <?php if ($multi): ?>
        <button type="button" class="ads-arrow prev" id="ads-prev" aria-label="Publicité précédente">‹</button>
        <button type="button" class="ads-arrow next" id="ads-next" aria-label="Publicité suivante">›</button>
        <div class="ads-dots" id="ads-dots"></div>
    <?php endif; ?>
</section>

<!-- ═════════ PRODUITS ═════════ -->
<section class="shop" id="produits">
    <div class="section-head">
        <div>
            <span class="eyebrow">Notre collection</span>
            <h2 class="h-serif">Nos produits</h2>
        </div>
        <div class="tools">
            <label class="search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/></svg>
                <input type="search" id="search" placeholder="Rechercher un produit…" autocomplete="off">
            </label>
        </div>
    </div>

    <?php if ($cats): ?>
    <div class="chips" role="group" aria-label="Catégories">
        <button type="button" class="chip active" data-cat="all">Tous</button>
        <?php foreach ($cats as $c): ?>
            <button type="button" class="chip" data-cat="<?= e($c) ?>"><?= e($c) ?></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$produits): ?>
        <p class="empty">Aucun produit pour le moment. Revenez très bientôt ✦</p>
    <?php else: ?>
    <div class="grid" id="grid">
        <?php foreach ($produits as $p):
            $stock = (int)$p['quantite_stock'];
            $out   = $stock <= 0;
            $low   = !$out && $stock <= 5;
            $img   = img_url($p['image'] ?? '');
            $statusClass = $out ? 'out' : ($low ? 'low' : 'ok');
            $statusText  = $out ? 'Out of stock' : ($low ? "Plus que $stock en stock" : 'Disponible');
        ?>
        <article class="card reveal<?= $out ? ' soldout' : '' ?>" data-cat="<?= e($p['categorie']) ?>" data-name="<?= e($p['nom']) ?>">
            <div class="card-media">
                <span class="ph" aria-hidden="true"><?= e(initial($p['nom'])) ?></span>
                <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($p['nom']) ?>" loading="lazy" onerror="this.remove()"><?php endif; ?>
                <?php if ($p['categorie']): ?><span class="tag"><?= e($p['categorie']) ?></span><?php endif; ?>
                <?php if ($out): ?><span class="soldout-badge">Out of stock</span><?php endif; ?>
            </div>
            <div class="card-body">
                <h3><?= e($p['nom']) ?></h3>
                <div class="card-row">
                    <span class="price"><?= e(money($p['prix'])) ?></span>
                    <span class="status <?= $statusClass ?>"><?= e($statusText) ?></span>
                </div>
                <form class="add-form" method="post" action="<?= e(url('panier_ajouter')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="produit_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-gold btn-block"<?= $out ? ' disabled' : '' ?>>
                        <span class="lbl"><?= $out ? 'Indisponible' : 'Ajouter au panier' ?></span>
                    </button>
                </form>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <p class="empty" id="empty" hidden>Aucun produit ne correspond à votre recherche.</p>
    <?php endif; ?>
</section>
