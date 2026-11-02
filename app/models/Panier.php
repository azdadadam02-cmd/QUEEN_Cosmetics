<?php defined('APP_ROOT') || exit;
/** Panier stocké en session : [ produit_id => quantité ] */
class Panier
{
    public const MAX_LIGNES = 30;   // produits différents
    public const MAX_QTE    = 20;   // par produit

    private static function lire(): array { $c = $_SESSION['panier'] ?? []; return is_array($c) ? $c : []; }
    private static function ecrire(array $c): void { $_SESSION['panier'] = $c; }

    public static function compter(): int { return (int)array_sum(self::lire()); }
    public static function quantite(int $id): int { return (int)(self::lire()[$id] ?? 0); }
    public static function vider(): void { unset($_SESSION['panier']); }

    /** @return array [bool ok, string message] */
    public static function ajouter(int $id, int $qte = 1): array {
        $p = (new Produit())->trouver($id);
        if (!$p) return [false, 'Produit introuvable.'];
        $stock = (int)$p['quantite_stock'];
        if ($stock <= 0) return [false, '« ' . $p['nom'] . ' » est en rupture de stock.'];

        $c = self::lire();
        $actuel = (int)($c[$id] ?? 0);
        if (!isset($c[$id]) && count($c) >= self::MAX_LIGNES) return [false, 'Panier plein (' . self::MAX_LIGNES . ' produits maximum).'];
        if ($actuel >= self::MAX_QTE) return [false, 'Quantité maximale (' . self::MAX_QTE . ') atteinte pour « ' . $p['nom'] . ' ».'];
        if ($actuel >= $stock) return [false, 'Stock maximum atteint pour « ' . $p['nom'] . ' ».'];

        $c[$id] = min($stock, self::MAX_QTE, $actuel + max(1, $qte));
        self::ecrire($c);
        return [true, '« ' . $p['nom'] . ' » ajouté au panier.'];
    }

    public static function fixer(int $id, int $qte): void {
        $c = self::lire();
        if ($qte < 1) { unset($c[$id]); } elseif (isset($c[$id])) { $c[$id] = min($qte, self::MAX_QTE); }
        self::ecrire($c);
    }

    /**
     * Lignes détaillées depuis la base (prix & stock toujours à jour).
     * Retire / ajuste automatiquement ce qui n'est plus disponible.
     */
    public static function lignes(): array {
        $out  = ['lignes' => [], 'total' => 0.0, 'articles' => 0, 'avertissements' => []];
        $cart = self::lire();
        if (!$cart) return $out;

        $produits = (new Produit())->parIds(array_keys($cart));
        $maj = [];
        foreach ($cart as $id => $qte) {
            $id = (int)$id; $qte = (int)$qte;
            $p  = $produits[$id] ?? null;
            if (!$p) { $out['avertissements'][] = 'Un produit n\'existe plus et a été retiré du panier.'; continue; }

            $stock = (int)$p['quantite_stock'];
            if ($stock <= 0) { $out['avertissements'][] = '« ' . $p['nom'] . ' » n\'est plus disponible : retiré du panier.'; continue; }
            if ($qte > $stock) { $qte = $stock; $out['avertissements'][] = 'Quantité de « ' . $p['nom'] . ' » ajustée au stock disponible (' . $stock . ').'; }
            if ($qte < 1) continue;

            $prix = (float)$p['prix'];
            $out['lignes'][] = [
                'id' => $id, 'nom' => $p['nom'], 'categorie' => $p['categorie'], 'image' => $p['image'],
                'prix' => $prix, 'quantite' => $qte, 'stock' => $stock, 'sous_total' => $prix * $qte,
            ];
            $out['total']    += $prix * $qte;
            $out['articles'] += $qte;
            $maj[$id] = $qte;
        }
        if ($maj !== $cart) self::ecrire($maj);
        return $out;
    }
}
