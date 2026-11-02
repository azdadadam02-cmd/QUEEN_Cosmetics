<?php defined('APP_ROOT') || exit;
/** Responsable commandes : accepte / refuse (annule) / marque livrée */
class GestionCommandesController
{
    public function gestion(): void {
        require_role(['responsable_commandes']);
        $filtre = $_GET['statut'] ?? '';
        if (!in_array($filtre, ['En attente', 'Acceptee', 'Livree', 'Refusee'], true)) $filtre = '';
        $model = new Commande();
        staff_render('commandes/gestion', [
            'title'     => 'Gestion des commandes',
            'commandes' => $model->toutes($filtre ?: null),
            'compteurs' => $model->compteurs(),
            'filtre'    => $filtre,
        ], 'commandes_gestion');
    }

    /** POST — changer le statut d'une commande */
    public function statut(): void {
        require_role(['responsable_commandes']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('commandes_gestion');
        csrf_check();

        $id      = (int)($_POST['id'] ?? 0);
        $nouveau = $_POST['statut'] ?? '';
        $labels  = ['Acceptee' => 'acceptée', 'Refusee' => 'refusée (stock remis en rayon)', 'Livree' => 'marquée comme livrée (paiement encaissé)'];
        try {
            if (!isset($labels[$nouveau])) throw new RuntimeException('Action invalide.');
            (new Commande())->changerStatut($id, $nouveau);
            flash_add('success', "Commande n° $id $labels[$nouveau].");
        } catch (RuntimeException $e) {
            flash_add('error', $e->getMessage());
        }
        redirect_back('commandes_gestion');
    }
}
