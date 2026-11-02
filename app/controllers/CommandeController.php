<?php
/**
 * app/controllers/CommandeController.php
 * ROLE    : actions du RESPONSABLE DES COMMANDES : consulter les commandes,
 *           accepter, refuser, marquer comme livrée.
 * POURQUOI : le contrôleur reçoit la demande, vérifie le rôle, appelle le modèle Commande,
 *            puis affiche un message (succès / erreur) et redirige. Aucun SQL ici.
 *
 * NB : le panier, la validation de commande et « mes commandes » (côté client)
 *      sont gérés par le contrôleur du client, pas ici.
 */
class CommandeController
{
    /**
     * Page de gestion : toutes les commandes avec leurs produits.
     * Le responsable commandes peut agir ; l'admin peut seulement CONSULTER
     * (cahier des charges : « l'administrateur consulte les commandes »).
     */
    public function gestion(): void
    {
        require_role('responsable_commandes', 'admin');

        $commandes = Commande::toutes();

        render('commandes/gestion', [
            'titre'         => 'Gestion des commandes',
            'commandes'     => $commandes,
            // Une seule requête pour les lignes de TOUTES les commandes (au lieu d'une par commande)
            'details'       => Commande::details(array_column($commandes, 'id')),
            // Vrai pour l'admin : la vue cache alors les boutons d'action
            'lecture_seule' => current_user()['role'] === 'admin',
        ]);
    }

    /** Accepter : vérifie le stock, le diminue, passe le statut à « Acceptee » (tout dans le modèle). */
    public function accepter(): void
    {
        $this->agir(fn(int $id) => Commande::accepter($id), 'Commande acceptée : le stock a été mis à jour.');
    }

    /** Refuser : « En attente » -> « Refusee ». Le stock n'est pas touché. */
    public function refuser(): void
    {
        $this->agir(fn(int $id) => Commande::refuser($id), 'Commande refusée.');
    }

    /** Livrer : « Acceptee » -> « Livree ». Après cela, le paiement pourra être confirmé. */
    public function livrer(): void
    {
        $this->agir(fn(int $id) => Commande::livrer($id), 'Commande marquée comme livrée.');
    }

    /**
     * Code commun aux 3 actions (accepter / refuser / livrer) : on l'écrit UNE seule fois.
     * $fonction = ce que le modèle doit faire ; $messageOk = message affiché si tout s'est bien passé.
     */
    private function agir(callable $fonction, string $messageOk): void
    {
        require_role('responsable_commandes');   // seul le responsable commandes agit (pas l'admin)
        verify_csrf();                           // ces actions modifient des données => POST + jeton

        try {
            $fonction((int)($_POST['id'] ?? 0));
            flash('success', $messageOk);
        } catch (RuntimeException $e) {
            // Ex : « Stock insuffisant pour Mascara (disponible : 2, demandé : 5) »
            flash('error', $e->getMessage());
        }
        redirect('commandes', 'gestion');        // évite de refaire l'action si on rafraîchit la page
    }
}