<?php /** @var array $panier, $user */ ?>

<section class="page-head">
    <span class="eyebrow">Dernière étape</span>
    <h1 class="h-serif">Confirmation de la commande</h1>
</section>

<div class="cart-layout">
    <div class="stack">

        <section class="panel">
            <h3>Vos articles</h3>
            <?php foreach ($panier['lignes'] as $l): $img = img_url($l['image'] ?? ''); ?>
            <div class="mini-line">
                <div class="thumb sm"><span class="ph"><?= e(initial($l['nom'])) ?></span>
                    <?php if ($img): ?><img src="<?= e($img) ?>" alt="" onerror="this.remove()"><?php endif; ?>
                </div>
                <div class="ml-info"><strong><?= e($l['nom']) ?></strong><small>Quantité : <?= (int)$l['quantite'] ?> × <?= e(money($l['prix'])) ?></small></div>
                <div class="ml-total"><?= e(money($l['sous_total'])) ?></div>
            </div>
            <?php endforeach; ?>
        </section>

        <section class="panel">
            <h3>Mode de paiement</h3>
            <label class="pay selected">
                <input type="radio" name="paiement" value="Livraison" checked>
                <span class="pay-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5v.01M18 14.5v.01"/></svg></span>
                <span class="pay-txt"><strong>Paiement à la livraison</strong><small>Vous réglez en espèces à la réception de votre commande.</small></span>
                <span class="pay-check" aria-hidden="true">✓</span>
            </label>
            <label class="pay disabled">
                <input type="radio" name="paiement" value="Carte" disabled>
                <span class="pay-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/></svg></span>
                <span class="pay-txt"><strong>Carte bancaire</strong><small>Bientôt disponible.</small></span>
            </label>
        </section>

        <section class="panel">
            <h3>Votre compte</h3>
            <p class="who"><strong><?= e($user['nom']) ?></strong><br><span><?= e($user['email']) ?></span></p>
        </section>

    </div>

    <aside class="summary">
        <h3 class="h-serif">À payer</h3>
        <dl>
            <div><dt>Articles</dt><dd><?= (int)$panier['articles'] ?></dd></div>
            <div><dt>Paiement</dt><dd>À la livraison</dd></div>
        </dl>
        <div class="total"><span>Total</span><strong><?= e(money($panier['total'])) ?></strong></div>
        <form method="post" action="<?= e(url('commande_valider')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-gold btn-block">Valider ma commande</button>
        </form>
        <a class="sum-link" href="<?= e(url('panier')) ?>">← Modifier mon panier</a>
    </aside>
</div>
