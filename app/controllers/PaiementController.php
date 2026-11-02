<?php
/**
 * app/controllers/PaiementController.php
 * ROLE : consulter les paiements (paiement a la livraison) et confirmer qu'une
 *        commande livree a ete payee. Reserve au responsable des commandes ;
 *        l'admin peut seulement consulter.
 */
class PaiementController
{
    public function gestion(): void
    {
        require_role('responsable_commandes', 'admin');

        render('paiements/gestion', [
            'titre'         => 'Gestion des paiements',
            'paiements'     => Paiement::tous(),
            'lecture_seule' => current_user()['role'] === 'admin',
        ]);
    }

    public function confirmer(): void
    {
        require_role('responsable_commandes');
        verify_csrf();

        try {
            Paiement::confirmer((int)($_POST['commande_id'] ?? 0));
            flash('success', 'Paiement confirmé.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect('paiements', 'gestion');
    }
}
