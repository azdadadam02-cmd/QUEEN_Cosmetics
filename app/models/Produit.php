<?php
class Produit
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? db(); }

    /** Tous les produits — disponibles d'abord, puis les plus récents */
    public function tous(): array {
        return $this->db->query('SELECT * FROM produits ORDER BY (quantite_stock <= 0), id DESC')
                        ->fetchAll(PDO::FETCH_ASSOC);
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
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $out[(int)$r['id']] = $r; }
        return $out;
    }
}
