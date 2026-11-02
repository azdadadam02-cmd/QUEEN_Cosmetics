<?php defined('APP_ROOT') || exit;
class Produit
{
    private PDO $db;
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? db();
        $this->assurerColonne();
    }

    /** Colonne promotion (% de réduction, 0 = aucune) — ajoutée automatiquement si absente */
    private function assurerColonne(): void {
        if (!empty($_SESSION['_schema_promo'])) return;
        try { $this->db->query('SELECT promotion FROM produits LIMIT 0'); }
        catch (Throwable $e) {
            try { $this->db->exec('ALTER TABLE produits ADD COLUMN promotion INT NOT NULL DEFAULT 0'); }
            catch (Throwable $e2) { error_log('[QUEEN] produits.promotion: ' . $e2->getMessage()); }
        }
        $_SESSION['_schema_promo'] = 1;
    }

    /** Tous les produits — disponibles d'abord, puis les plus récents */
    public function tous(): array {
        return $this->avecPromos($this->db->query('SELECT * FROM produits ORDER BY (quantite_stock <= 0), id DESC')
                        ->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Ajoute promo (%) + prix_original et remplace prix par le prix réduit — côté client uniquement */
    private function avecPromos(array $rows): array {
        $promos = (new Publicite($this->db))->promosActives();
        foreach ($rows as &$r) {
            $r['promo'] = 0; $r['prix_original'] = (float)$r['prix'];
            $pc = max((int)($r['promotion'] ?? 0), $promos[(int)$r['id']] ?? 0);
            if ($pc > 0) {
                $r['promo'] = $pc;
                $r['prix']  = round((float)$r['prix'] * (100 - $pc) / 100, 2);
            }
        }
        unset($r);
        return $rows;
    }
    public function categories(): array {
        return $this->db->query("SELECT DISTINCT categorie FROM produits WHERE categorie IS NOT NULL AND categorie <> '' ORDER BY categorie")
                        ->fetchAll(PDO::FETCH_COLUMN);
    }
    public function trouver(int $id): ?array {
        $st = $this->db->prepare('SELECT * FROM produits WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    /** @return array<int,array> produits indexés par id */
    public function parIds(array $ids): array {
        if (!$ids) return [];
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare("SELECT * FROM produits WHERE id IN ($in)");
        $st->execute(array_map('intval', array_values($ids)));
        $out = [];
        foreach ($this->avecPromos($st->fetchAll(PDO::FETCH_ASSOC)) as $r) { $out[(int)$r['id']] = $r; }
        return $out;
    }

    /** Liste back-office : plus récents d'abord */
    public function liste(): array {
        return $this->db->query('SELECT * FROM produits ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
    public function creer(string $nom, ?string $categorie, float $prix, int $stock, ?string $image, int $promo = 0): int {
        $st = $this->db->prepare('INSERT INTO produits (nom, categorie, prix, quantite_stock, image, promotion) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([$nom, $categorie ?: null, round($prix, 2), $stock, $image ?: null, $promo]);
        return (int)$this->db->lastInsertId();
    }
    public function stats(): array {
        $r = $this->db->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN quantite_stock <= 0 THEN 1 ELSE 0 END), 0) AS rupture,
                    COALESCE(SUM(CASE WHEN quantite_stock > 0 AND quantite_stock <= 5 THEN 1 ELSE 0 END), 0) AS faible,
                    COALESCE(SUM(quantite_stock), 0) AS unites
               FROM produits')->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $r ?: []);
    }

    /** Modifie un produit ; $image = null → l'image actuelle est conservée */
    public function modifier(int $id, string $nom, ?string $categorie, float $prix, int $stock, ?string $image = null, int $promo = 0): bool {
        if ($image !== null) {
            $st = $this->db->prepare('UPDATE produits SET nom = ?, categorie = ?, prix = ?, quantite_stock = ?, image = ?, promotion = ? WHERE id = ?');
            $st->execute([$nom, $categorie ?: null, round($prix, 2), $stock, $image, $promo, $id]);
        } else {
            $st = $this->db->prepare('UPDATE produits SET nom = ?, categorie = ?, prix = ?, quantite_stock = ?, promotion = ? WHERE id = ?');
            $st->execute([$nom, $categorie ?: null, round($prix, 2), $stock, $promo, $id]);
        }
        return $st->rowCount() > 0 || $this->trouver($id) !== null;
    }
}
