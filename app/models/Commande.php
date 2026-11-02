<?php defined('APP_ROOT') || exit;
class Commande
{
    private PDO $db;
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? db();
        $this->assurerColonnesLivraison();
    }

    /** Colonnes d'adresse de livraison (ajoutées automatiquement si absentes, une fois par session) */
    private function assurerColonnesLivraison(): void {
        if (!empty($_SESSION['_schema_livraison'])) return;
        try {
            $this->db->query('SELECT nom_livraison, telephone, ville, adresse FROM commandes LIMIT 1');
        } catch (Throwable $e) {
            foreach (['nom_livraison VARCHAR(100)', 'telephone VARCHAR(20)', 'ville VARCHAR(80)', 'adresse VARCHAR(255)'] as $def) {
                try { $this->db->exec("ALTER TABLE commandes ADD COLUMN $def"); } catch (Throwable $e2) { /* existe déjà */ }
            }
        }
        $_SESSION['_schema_livraison'] = true;
    }

    /**
     * Enregistre commande + détails + paiement (livraison) dans UNE transaction
     * et décrémente le stock sans jamais passer sous 0.
     * @param array $lignes [ ['id','nom','prix','quantite'], … ]
     */
    public function creer(int $clientId, array $lignes, array $livraison = []): int {
        if (!$lignes) throw new RuntimeException('Votre panier est vide.');
        $total = 0.0;
        foreach ($lignes as $l) { $total += $l['prix'] * $l['quantite']; }

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("INSERT INTO commandes (client_id, total, statut, nom_livraison, telephone, ville, adresse) VALUES (?, ?, 'En attente', ?, ?, ?, ?)");
            $st->execute([$clientId, round($total, 2), $livraison['nom'] ?? null, $livraison['telephone'] ?? null, $livraison['ville'] ?? null, $livraison['adresse'] ?? null]);
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

    /** Nombre de commandes en attente d'un client (limite anti-spam) */
    public function nbEnAttente(int $clientId): int {
        $st = $this->db->prepare("SELECT COUNT(*) FROM commandes WHERE client_id = ? AND statut = 'En attente'");
        $st->execute([$clientId]);
        return (int)$st->fetchColumn();
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
            'SELECT c.id, c.date_commande, c.total, c.statut, c.nom_livraison, c.telephone, c.ville, c.adresse, p.methode, p.statut AS paiement_statut
               FROM commandes c LEFT JOIN paiements p ON p.commande_id = c.id
              WHERE c.client_id = ? ORDER BY c.date_commande DESC, c.id DESC');
        $st->execute([$clientId]);
        $commandes = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$commandes) return [];

        return $this->attacherArticles($commandes);
    }

    /** Ajoute 'articles' à chaque commande */
    private function attacherArticles(array $commandes): array {
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
        unset($c);
        return $commandes;
    }

    /* ───────────── Back-office ───────────── */

    /** Toutes les commandes (avec client, paiement, articles), filtre statut optionnel */
    public function toutes(?string $statut = null): array {
        $sql = 'SELECT c.id, c.client_id, c.date_commande, c.total, c.statut,
                       c.nom_livraison, c.telephone, c.ville, c.adresse,
                       u.nom AS client_nom, u.email AS client_email,
                       p.methode, p.statut AS paiement_statut
                  FROM commandes c
                  LEFT JOIN utilisateurs u ON u.id = c.client_id
                  LEFT JOIN paiements p ON p.commande_id = c.id';
        $args = [];
        if ($statut) { $sql .= ' WHERE c.statut = ?'; $args[] = $statut; }
        $sql .= ' ORDER BY c.date_commande DESC, c.id DESC';
        $st = $this->db->prepare($sql);
        $st->execute($args);
        return $this->attacherArticles($st->fetchAll(PDO::FETCH_ASSOC));
    }

    /** statut => nombre */
    public function compteurs(): array {
        $out = [];
        foreach ($this->db->query('SELECT statut, COUNT(*) AS n FROM commandes GROUP BY statut')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[$r['statut']] = (int)$r['n'];
        }
        return $out;
    }

    /** Transitions autorisées pour le responsable des commandes */
    public const TRANSITIONS = [
        'En attente' => ['Acceptee', 'Refusee'],
        'Acceptee'   => ['Livree', 'Refusee'],
    ];

    /**
     * Accepter / refuser / marquer livrée.
     * Refus → le stock réservé est remis en rayon. Livraison → paiement marqué « Paye ».
     */
    public function changerStatut(int $id, string $nouveau): void {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT statut FROM commandes WHERE id = ?');
            $st->execute([$id]);
            $actuel = $st->fetchColumn();
            if ($actuel === false) throw new RuntimeException('Commande introuvable.');
            if (!in_array($nouveau, self::TRANSITIONS[$actuel] ?? [], true)) {
                throw new RuntimeException("Cette commande ne peut pas passer de « $actuel » à « $nouveau ».");
            }
            $up = $this->db->prepare('UPDATE commandes SET statut = ? WHERE id = ? AND statut = ?');
            $up->execute([$nouveau, $id, $actuel]);
            if ($up->rowCount() < 1) throw new RuntimeException("Cette commande vient d'être modifiée par quelqu'un d'autre.");

            if ($nouveau === 'Refusee') {
                $d = $this->db->prepare('SELECT produit_id, quantite FROM details_commandes WHERE commande_id = ?');
                $d->execute([$id]);
                $back = $this->db->prepare('UPDATE produits SET quantite_stock = quantite_stock + ? WHERE id = ?');
                foreach ($d->fetchAll(PDO::FETCH_ASSOC) as $l) { $back->execute([$l['quantite'], $l['produit_id']]); }
            } elseif ($nouveau === 'Livree') {
                $this->db->prepare("UPDATE paiements SET statut = 'Paye' WHERE commande_id = ?")->execute([$id]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof RuntimeException) throw $e;
            error_log('[QUEEN] ' . $e->getMessage());
            throw new RuntimeException('Impossible de modifier la commande.');
        }
    }
}
