<?php
/**
 * app/models/Paiement.php
 * ROLE    : parler à la table `paiements`. Un seul mode de paiement : à la LIVRAISON.
 *           Le responsable commandes peut consulter les paiements et confirmer qu'une
 *           commande livrée a bien été payée.
 * POURQUOI : la logique SQL des paiements est isolée ici, comme pour Produit et Commande.
 *
 * NB : la ligne de paiement (statut « Non paye ») est créée par la personne qui s'occupe de
 *      la création de commande (client), en même temps que la commande.
 */
class Paiement
{
    // Statuts EXACTEMENT comme dans l'ENUM de la base (sans accents).
    public const NON_PAYE = 'Non paye';
    public const PAYE     = 'Paye';

    /**
     * Tous les paiements avec la commande et le client concernés.
     * POURQUOI les JOIN : une seule requête pour afficher « client, commande, montant, statuts ».
     */
    public static function tous(): array
    {
        return Database::get()->query(
            'SELECT p.*, c.statut AS commande_statut, c.date_commande,
                    u.nom AS client_nom, u.email AS client_email
             FROM paiements p
             JOIN commandes c    ON c.id = p.commande_id
             JOIN utilisateurs u ON u.id = c.client_id
             ORDER BY p.id DESC'
        )->fetchAll();
    }

    /**
     * CONFIRMER le paiement d'une commande (le client a payé le livreur).
     * Règle : on ne peut confirmer que si
     *   - la commande est « Livree » (on paie à la livraison, donc APRÈS la livraison) ;
     *   - le paiement est encore « Non paye » (pas de double confirmation).
     *
     * POURQUOI ces deux conditions dans le WHERE (et pas seulement dans la vue) :
     *   même si quelqu'un force l'URL, la base refuse. Si aucune ligne n'est modifiée
     *   (rowCount() = 0), la règle n'était pas respectée => on lève une erreur.
     * POURQUOI EXISTS : on vérifie le statut de la commande avec une sous-requête ;
     *   c'est du SQL standard, simple à comprendre.
     */
    public static function confirmer(int $commandeId): void
    {
        $st = Database::get()->prepare(
            'UPDATE paiements
             SET statut = ?
             WHERE commande_id = ?
               AND statut = ?
               AND EXISTS (SELECT 1 FROM commandes WHERE id = ? AND statut = ?)'
        );
        $st->execute([self::PAYE, $commandeId, self::NON_PAYE, $commandeId, 'Livree']);

        if ($st->rowCount() !== 1) {
            throw new RuntimeException(
                'Le paiement ne peut être confirmé que pour une commande livrée et non encore payée.'
            );
        }
    }
}