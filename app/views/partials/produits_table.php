<?php defined('APP_ROOT') || exit;
/** @var array $produits  @var bool $editable (bouton Modifier) */
$editable = $editable ?? false; ?>
<div class="panel tablewrap">
    <div class="toolbar">
        <label class="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Rechercher un produit…" data-filter="#tbl-produits" autocomplete="off">
        </label>
        <span class="count"><?= count($produits) ?> produit<?= count($produits) > 1 ? 's' : '' ?></span>
    </div>
    <table class="tbl" id="tbl-produits">
        <thead><tr><th>Produit</th><th>Catégorie</th><th class="num">Prix</th><th class="num">Stock</th><th class="num">Promo</th><th>État</th><?= $editable ? '<th class="right">Actions</th>' : '' ?></tr></thead>
        <tbody>
        <?php foreach ($produits as $p): [$lib, $cls] = stock_info((int)$p['quantite_stock']); ?>
            <tr>
                <td>
                    <div class="cell-prod">
                        <span class="thumb xs"><span class="ph"><?= e(initial($p['nom'])) ?></span>
                            <?php if (!empty($p['image'])): ?><img src="<?= e(img_url($p['image'])) ?>" alt="" loading="lazy" data-fallback><?php endif; ?>
                        </span>
                        <strong><?= e($p['nom']) ?></strong>
                    </div>
                </td>
                <td><?= e($p['categorie'] ?: '—') ?></td>
                <td class="num"><?= e(money($p['prix'])) ?></td>
                <td class="num"><b><?= (int)$p['quantite_stock'] ?></b></td>
                <td class="num"><?php $pr = (int)($p['promotion'] ?? 0); echo $pr > 0 ? '<span class="pill pill-promo">-' . $pr . '%</span>' : '—'; ?></td>
                <td><span class="pill pill-<?= e($cls) ?>"><?= e($lib) ?></span></td>
                <?php if ($editable): ?>
                <td class="right act">
                    <button type="button" class="btn-ghost" data-prod-edit
                            data-id="<?= (int)$p['id'] ?>"
                            data-nom="<?= e($p['nom']) ?>"
                            data-categorie="<?= e($p['categorie'] ?? '') ?>"
                            data-prix="<?= e($p['prix']) ?>"
                            data-stock="<?= (int)$p['quantite_stock'] ?>"
                            data-promo="<?= (int)($p['promotion'] ?? 0) ?>"
                            data-image="<?= e(img_url($p['image'] ?? '')) ?>">Modifier</button>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$produits): ?><tr><td colspan="<?= $editable ? 7 : 6 ?>" class="empty-row">Aucun produit pour le moment.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
