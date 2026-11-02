<?php
class CommandeController
{
    /** GET — page de confirmation : récapitulatif + paiement à la livraison */
    public function confirmation(): void {
        $user = current_user();
        if (!$user) {
            flash_add('info', 'Connectez-vous pour confirmer votre commande.');
            redirect('login');
        }
        $panier = Panier::lignes();
        foreach ($panier['avertissements'] as $w) { flash_add('info', $w); }
        if (!$panier['lignes']) {
            flash_add('info', 'Votre panier est vide.');
            redirect('panier');
        }
        render('client/confirmation', ['title' => 'Confirmation', 'panier' => $panier, 'user' => $user], 'panier');
    }

    /** POST — crée la commande (paiement à la livraison) */
    public function valider(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('panier');
        csrf_check();

        $user = current_user();
        if (!$user) redirect('login');

        $panier = Panier::lignes();
        if (!$panier['lignes']) {
            flash_add('info', 'Votre panier est vide.');
            redirect('panier');
        }
        try {
            $id = (new Commande())->creer($user['id'], $panier['lignes']);
            Panier::vider();
            flash_add('success', "Commande #$id enregistrée — vous réglerez à la livraison.");
            redirect('notifications');
        } catch (RuntimeException $e) {
            flash_add('error', $e->getMessage());
            redirect('panier');
        }
    }
}
