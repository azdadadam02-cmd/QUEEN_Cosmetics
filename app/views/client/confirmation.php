<?php defined('APP_ROOT') || exit;
/** @var array $panier, $user, $old */ ?>

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
                    <?php if ($img): ?><img src="<?= e($img) ?>" alt="" data-fallback><?php endif; ?>
                </div>
                <div class="ml-info"><strong><?= e($l['nom']) ?></strong><small>Quantité : <?= (int)$l['quantite'] ?> × <?= e(money($l['prix'])) ?></small></div>
                <div class="ml-total"><?= e(money($l['sous_total'])) ?></div>
            </div>
            <?php endforeach; ?>
        </section>

        <section class="panel">
            <h3>Informations de livraison</h3>
            <div class="ship-form">
                <div class="field">
                    <label for="l_nom">Nom de la personne qui reçoit</label>
                    <input id="l_nom" name="nom" form="checkout" type="text" maxlength="100" required autocomplete="name" value="<?= e($old['nom']) ?>">
                </div>
                <div class="row2">
                    <div class="field">
                        <label for="l_tel">Téléphone</label>
                        <input id="l_tel" name="telephone" form="checkout" type="tel" inputmode="tel" required autocomplete="tel" placeholder="06 12 34 56 78" value="<?= e($old['telephone']) ?>">
                    </div>
                    <div class="field">
                        <label for="l_ville">Ville</label>
                        <input id="l_ville" name="ville" form="checkout" type="text" maxlength="80" required autocomplete="address-level2" placeholder="Casablanca" value="<?= e($old['ville']) ?>">
                    </div>
                </div>
                <div class="field">
                    <label for="l_adr">Adresse complète</label>
                    <textarea id="l_adr" name="adresse" form="checkout" rows="3" maxlength="255" required autocomplete="street-address" placeholder="N°, rue, quartier, repère…"><?= e($old['adresse']) ?></textarea>
                </div>
                <p class="hint">Notre livreur vous appellera sur ce numéro avant de passer.</p>
            </div>
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
        <form id="checkout" method="post" action="<?= e(url('commande_valider')) ?>">
            <?= csrf_field() ?>
            <label class="check" for="majeur">
                <input type="checkbox" id="majeur" name="majeur" value="1" required>
                <span class="box" aria-hidden="true"></span>
                <span class="check-txt"><b>+18</b> Je confirme avoir 18 ans ou plus.</span>
            </label>
            <button type="submit" class="btn btn-gold btn-block" id="btn-valider" disabled>Valider ma commande</button>
        </form>
        <a class="sum-link" href="<?= e(url('panier')) ?>">← Modifier mon panier</a>
    </aside>
</div>
