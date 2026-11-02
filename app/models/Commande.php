<?php
/**
 * app/models/Commande.php
 * ROLE    : parler aux tables `commandes` et `details_commandes` pour le RESPONSABLE DES COMMANDES :
 *           consulter les commandes, accepter (si stock suffisant), refuser, marquer livrée.
 * POURQUOI : toute la logique SQL des commandes est ici ; le contrôleur appelle juste
 *            Commande::accepter($id) sans savoir comment le stock est vérifié et mis à jour.
 *
 * NB : la création d'une commande (validation du panier) appartient au client ;
 *      elle s'ajoute dans ce même modèle par la personne qui s'en occupe.
 */
class Commande
{
    // Statuts EXACTEMENT comme dans l'ENUM de la base (sans accents).
    public const ATTENTE  = 'En attente';
    public const ACCEPTEE = 'Acceptee';
    public const REFUSEE  = 'Refusee';
    public const LIVREE   = 'Livree';

    /**
     * Toutes les commandes, avec le nom du client et le statut du paiement.
     * POURQUOI les JOIN : on affiche tout en UNE requête (au lieu d'une requête par commande).
     * LEFT JOIN paiements : si une commande n'a pas (encore) de ligne paiement, elle s'affiche quand même.
     */
    public static function toutes(): array
    {
        return Database::get()->query(
            'SELECT c.*, u.nom AS client_nom, u.email AS client_email, p.statut AS paiement_statut
             FROM commandes c
             JOIN utilisateurs u      ON u.id = c.client_id
             LEFT JOIN paiements p    ON p.commande_id = c.id
             ORDER BY c.id DESC'
        )->fetchAll();
    }

    /**
     * Lignes (produits) de plusieurs commandes d'un coup.
     * Retourne : [ id_commande => [ligne, ligne, ...], ... ]
     * On joint `produits` pour afficher le nom et le stock ACTUEL (utile pour décider d'accepter).
     */
    public static function details(array $commandeIds): array
    {
        if (!$commandeIds) return [];
        $in = implode(',', array_fill(0, count($commandeIds), '?')); // ?,?,? selon le nombre d'ids
        $st = Database::get()->prepare(
            "SELECT d.*, pr.nom, pr.quantite_stock
             FROM details_commandes d
             JOIN produits pr ON pr.id = d.produit_id
             WHERE d.commande_id IN ($in)
             ORDER BY d.id"
        );
        $st->execute(array_values($commandeIds));

        $out = [];
        foreach ($st->fetchAll() as $ligne) {
            $out[$ligne['commande_id']][] = $ligne;
        }
        return $out;
    }

    /**
     * ACCEPTER une commande.
     * Règles du cahier des charges :
     *   - seule une commande « En attente » peut être acceptée ;
     *   - refusée si le stock est insuffisant pour UN des produits ;
     *   - le stock diminue UNIQUEMENT maintenant (pas à la création de la commande).
     *
     * POURQUOI une transaction (beginTransaction / commit / rollBack) :
     *   « vérifier le stock » + « diminuer le stock » + « changer le statut » doivent réussir ENSEMBLE
     *   ou pas du tout. Si une étape échoue, rollBack() annule tout : jamais de stock à moitié modifié.
     * POURQUOI « FOR UPDATE » : verrouille les lignes le temps de la transaction. Si deux commandes
     *   demandent le dernier produit en même temps, la 2e attend et voit le stock déjà diminué.
     */
    public static function accepter(int $id): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            // 1. La commande doit exister et être « En attente »
            $st = $pdo->prepare('SELECT statut FROM commandes WHERE id = ? FOR UPDATE');
            $st->execute([$id]);
            if ($st->fetchColumn() !== self::ATTENTE) {
                throw new RuntimeException('Seule une commande « En attente » peut être acceptée.');
            }

            // 2. Récupérer les produits commandés
            $st = $pdo->prepare('SELECT produit_id, quantite FROM details_commandes WHERE commande_id = ?');
            $st->execute([$id]);
            $lignes = $st->fetchAll();

            // 3. Vérifier le stock de CHAQUE produit avant de modifier quoi que ce soit
            $verrou = $pdo->prepare('SELECT nom, quantite_stock FROM produits WHERE id = ? FOR UPDATE');
            foreach ($lignes as $l) {
                $verrou->execute([$l['produit_id']]);
                $p = $verrou->fetch();
                if (!$p || $p['quantite_stock'] < $l['quantite']) {
                    throw new RuntimeException(
                        'Stock insuffisant pour « ' . ($p['nom'] ?? 'produit supprimé') . ' » (disponible : '
                        . ($p['quantite_stock'] ?? 0) . ', demandé : ' . $l['quantite'] . ').'
                    );
                }
            }

            // 4. Tout est OK : diminuer le stock de chaque produit
            $dec = $pdo->prepare('UPDATE produits SET quantite_stock = quantite_stock - ? WHERE id = ?');
            foreach ($lignes as $l) {
                $dec->execute([$l['quantite'], $l['produit_id']]);
            }

            // 5. Passer la commande à « Acceptee »
            $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?')->execute([self::ACCEPTEE, $id]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();   // annule tout (stock inchangé)
            throw $e;           // le contrôleur affichera le message d'erreur
        }
    }

    /**
     * Changement de statut « simple » (refuser, livrer).
     * `WHERE id = ? AND statut = ?` = on vérifie l'ANCIEN statut dans la requête elle-même :
     * on ne peut donc pas, par exemple, livrer une commande refusée, même en forçant l'URL.
     * rowCount() = nombre de lignes modifiées ; 0 => la transition n'était pas permise.
     */
    private static function changerStatut(int $id, string $depuis, string $vers): void
    {
        $st = Database::get()->prepare('UPDATE commandes SET statut = ? WHERE id = ? AND statut = ?');
        $st->execute([$vers, $id, $depuis]);
        if ($st->rowCount() !== 1) {
            throw new RuntimeException("Action impossible : la commande n'est pas « $depuis ».");
        }
    }

    /** Refuser : possible seulement depuis « En attente » (le stock n'a jamais été touché). */
    public static function refuser(int $id): void
    {
        self::changerStatut($id, self::ATTENTE, self::REFUSEE);
    }

    /** Livrer : possible seulement depuis « Acceptee ». */
    public static function livrer(int $id): void
    {
        self::changerStatut($id, self::ACCEPTEE, self::LIVREE);
    }
}