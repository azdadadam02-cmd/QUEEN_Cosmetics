<?php defined('APP_ROOT') || exit;
class Publicite
{
    private PDO $db;
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? db();
        $this->assurerColonne();
    }

    /** Colonne produit_id (promo liée à un produit) — ajoutée automatiquement si absente */
    private function assurerColonne(): void {
        if (!empty($_SESSION['_schema_pub'])) return;
        try { $this->db->query('SELECT produit_id FROM publicites LIMIT 0'); }
        catch (Throwable $e) {
            try { $this->db->exec('ALTER TABLE publicites ADD COLUMN produit_id INT NULL'); }
            catch (Throwable $e2) { error_log('[QUEEN] publicites.produit_id: ' . $e2->getMessage()); }
        }
        $_SESSION['_schema_pub'] = 1;
    }

    /** @return array<int,int> produit_id => % de réduction (la plus forte si plusieurs) */
    public function promosActives(): array {
        $out = [];
        $rows = $this->db->query('SELECT produit_id, reduction FROM publicites WHERE produit_id IS NOT NULL AND reduction > 0')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) { $id = (int)$r['produit_id']; $out[$id] = max($out[$id] ?? 0, (int)$r['reduction']); }
        return $out;
    }

    public function toutes(): array {
        return $this->db->query('SELECT g.*, p.nom AS produit_nom FROM publicites g LEFT JOIN produits p ON p.id = g.produit_id ORDER BY g.id DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array {
        $st = $this->db->prepare('SELECT * FROM publicites WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function creer(string $titre, int $reduction, ?string $image, ?int $produitId = null): int {
        $st = $this->db->prepare('INSERT INTO publicites (titre, image, reduction, produit_id) VALUES (?, ?, ?, ?)');
        $st->execute([$titre, $image ?: null, $reduction, $produitId ?: null]);
        return (int)$this->db->lastInsertId();
    }
    /** $image = null → image conservée */
    public function modifier(int $id, string $titre, int $reduction, ?string $image = null, ?int $produitId = null): void {
        $pid = $produitId ?: null;
        if ($image !== null) {
            $this->db->prepare('UPDATE publicites SET titre = ?, reduction = ?, image = ?, produit_id = ? WHERE id = ?')->execute([$titre, $reduction, $image, $pid, $id]);
        } else {
            $this->db->prepare('UPDATE publicites SET titre = ?, reduction = ?, produit_id = ? WHERE id = ?')->execute([$titre, $reduction, $pid, $id]);
        }
    }
    public function supprimer(int $id): void {
        $this->db->prepare('DELETE FROM publicites WHERE id = ?')->execute([$id]);
    }
}
