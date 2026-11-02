<?php
class Commande
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? db(); }

    /**
     * Enregistre commande + détails + paiement (livraison) dans UNE transaction
     * et décrémente le stock sans jamais passer sous 0.
     * @param array $lignes [ ['id','nom','prix','quantite'], … ]
     */
    public function creer(int $clientId, array $lignes): int {
        if (!$lignes) throw new RuntimeException('Votre panier est vide.');
        $total = 0.0;
        foreach ($lignes as $l) { $total += $l['prix'] * $l['quantite']; }

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("INSERT INTO commandes (client_id, total, statut) VALUES (?, ?, 'En attente')");
            $st->execute([$clientId, round($total, 2)]);
            $id = (int)$this->db->lastInsertId();

            $stock = $this->db->prepare('UPDATE produits SET quantite_stock = quantite_stock - ? WHERE id = ? AND quantite_stock >= ?');
            $det   = $this->db->prepare('INSERT INTO details_commandes (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)');
            foreach ($lignes as $l) {
                $stock->execute([$l['quantite'], $l['id'], $l['quantite']]);
                if ($stock->rowCount() < 1) {
                    throw new RuntimeException('Stock insuffisant pour « ' . $l['nom'] . ' ».');
                }
                $det->execute([$id, $l['id'], $l['quantite'], $l['prix']]);
            }
            (new Paiement($this->db))->creer($id, $total, 'Livraison');
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof RuntimeException) throw $e;
            error_log('[QUEEN] ' . $e->getMessage());
            throw new RuntimeException("Impossible d'enregistrer la commande. Réessayez dans un instant.");
        }
    }

    /** id => statut (léger : sert au badge de notification) */
    public function statutsPourClient(int $clientId): array {
        $st = $this->db->prepare('SELECT id, statut FROM commandes WHERE client_id = ?');
        $st->execute([$clientId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $out[(int)$r['id']] = $r['statut']; }
        return $out;
    }

    /** Commandes du client + paiement + articles, les plus récentes d'abord */
    public function pourClient(int $clientId): array {
        $st = $this->db->prepare(
            'SELECT c.id, c.date_commande, c.total, c.statut, p.methode, p.statut AS paiement_statut
               FROM commandes c LEFT JOIN paiements p ON p.commande_id = c.id
              WHERE c.client_id = ? ORDER BY c.date_commande DESC, c.id DESC');
        $st->execute([$clientId]);
        $commandes = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$commandes) return [];

        $ids = array_column($commandes, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $st  = $this->db->prepare(
            "SELECT d.commande_id, d.quantite, d.prix_unitaire, pr.nom, pr.image
               FROM details_commandes d JOIN produits pr ON pr.id = d.produit_id
              WHERE d.commande_id IN ($in)");
        $st->execute($ids);
        $articles = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $articles[(int)$r['commande_id']][] = $r; }

        foreach ($commandes as &$c) { $c['articles'] = $articles[(int)$c['id']] ?? []; }
        return $commandes;
    }
}
