<?php defined('APP_ROOT') || exit;
class Paiement
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? db(); }

    /** Paiement à la livraison : créé « Non paye », l'admin le passe à « Paye » à la remise */
    public function creer(int $commandeId, float $montant, string $methode = 'Livraison'): void {
        $st = $this->db->prepare("INSERT INTO paiements (commande_id, montant, methode, statut) VALUES (?, ?, ?, 'Non paye')");
        $st->execute([$commandeId, round($montant, 2), $methode]);
    }
}
