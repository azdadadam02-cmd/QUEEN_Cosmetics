<?php defined('APP_ROOT') || exit;
/** Argent encaissé (paiements à la livraison) — lecture seule pour l'admin */
class Finance
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? db(); }

    /** Totaux : encaissé, ce mois, à encaisser, en attente de validation, refusé */
    public function resume(): array {
        $debutMois = date('Y-m-01 00:00:00');
        $st = $this->db->prepare(
            "SELECT
               COALESCE(SUM(CASE WHEN p.statut = 'Paye' THEN p.montant END), 0)                                         AS encaisse,
               COALESCE(SUM(CASE WHEN p.statut = 'Paye' AND c.date_commande >= ? THEN p.montant END), 0)                AS mois,
               COALESCE(SUM(CASE WHEN p.statut <> 'Paye' AND c.statut = 'Acceptee'   THEN p.montant END), 0)            AS a_encaisser,
               COALESCE(SUM(CASE WHEN p.statut <> 'Paye' AND c.statut = 'En attente' THEN p.montant END), 0)            AS en_attente,
               COALESCE(SUM(CASE WHEN c.statut = 'Refusee' THEN p.montant END), 0)                                      AS refuse,
               COALESCE(SUM(CASE WHEN p.statut = 'Paye' THEN 1 ELSE 0 END), 0)                                          AS nb_payes
             FROM paiements p JOIN commandes c ON c.id = p.commande_id");
        $st->execute([$debutMois]);
        $r = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $r = array_map('floatval', $r);
        $r['panier_moyen'] = $r['nb_payes'] > 0 ? $r['encaisse'] / $r['nb_payes'] : 0.0;
        return $r;
    }

    /** Encaissé par jour sur les $n derniers jours (jours sans vente = 0) */
    public function parJour(int $n = 14): array {
        $from = date('Y-m-d', strtotime('-' . ($n - 1) . ' days'));
        $st = $this->db->prepare(
            "SELECT DATE(c.date_commande) AS j, SUM(p.montant) AS m, COUNT(*) AS n
               FROM paiements p JOIN commandes c ON c.id = p.commande_id
              WHERE p.statut = 'Paye' AND c.date_commande >= ?
              GROUP BY DATE(c.date_commande)");
        $st->execute([$from . ' 00:00:00']);
        $map = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $map[$r['j']] = ['m' => (float)$r['m'], 'n' => (int)$r['n']]; }
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $out[] = ['jour' => $d, 'montant' => $map[$d]['m'] ?? 0.0, 'nb' => $map[$d]['n'] ?? 0];
        }
        return $out;
    }

    /** Liste des paiements ; $filtre : '' | 'paye' | 'encaisser' */
    public function paiements(string $filtre = ''): array {
        $sql = 'SELECT p.id, p.commande_id, p.montant, p.methode, p.statut AS paiement_statut,
                       c.date_commande, c.statut, u.nom AS client_nom, u.email AS client_email
                  FROM paiements p
                  JOIN commandes c ON c.id = p.commande_id
                  LEFT JOIN utilisateurs u ON u.id = c.client_id';
        if ($filtre === 'paye')           $sql .= " WHERE p.statut = 'Paye'";
        elseif ($filtre === 'encaisser')  $sql .= " WHERE p.statut <> 'Paye' AND c.statut IN ('Acceptee', 'En attente')";
        $sql .= ' ORDER BY c.date_commande DESC, p.id DESC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
