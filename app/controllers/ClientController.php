<?php defined('APP_ROOT') || exit;
class ClientController
{
    /** Page principale : bandeau publicitaire + produits */
    public function accueil(): void {
        $produit = new Produit();
        render('client/accueil', [
            'title'    => 'Accueil',
            'produits' => $produit->tous(),
            'cats'     => $produit->categories(),
            'pubs'     => (new Publicite())->toutes(),
        ], 'accueil');
    }

    /** Panier */
    public function panier(): void {
        $panier = Panier::lignes();
        foreach ($panier['avertissements'] as $w) { flash_add('info', $w); }
        render('client/panier', ['title' => 'Mon panier', 'panier' => $panier], 'panier');
    }

    /** POST — ajouter un produit (JSON si appel AJAX, sinon redirection) */
    public function panierAjouter(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('accueil');
        csrf_check();

        $id  = (int)($_POST['produit_id'] ?? 0);
        $qte = max(1, (int)($_POST['quantite'] ?? 1));
        [$ok, $msg] = Panier::ajouter($id, $qte);

        if (is_ajax()) {
            json_out(['ok' => $ok, 'message' => $msg, 'count' => Panier::compter()], $ok ? 200 : 409);
        }
        flash_add($ok ? 'success' : 'error', $msg);
        redirect_back('accueil');
    }

    /** POST — +, −, supprimer, vider */
    public function panierModifier(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('panier');
        csrf_check();

        $id     = (int)($_POST['produit_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'inc':
                [$ok, $msg] = Panier::ajouter($id, 1);
                if (!$ok) flash_add('error', $msg);
                break;
            case 'dec':
                Panier::fixer($id, Panier::quantite($id) - 1);
                break;
            case 'remove':
                Panier::fixer($id, 0);
                break;
            case 'clear':
                Panier::vider();
                break;
        }
        redirect('panier');
    }

    /** Notifications = suivi des commandes et de leur statut */
    public function notifications(): void {
        $user = current_user();
        if (!$user) {
            flash_add('info', 'Connectez-vous pour consulter vos notifications.');
            redirect('login');
        }

        $commandes = (new Commande())->pourClient($user['id']);
        $seen      = notif_seen($user['id']);
        $statuts   = [];
        foreach ($commandes as &$c) {
            $c['nouveau'] = (($seen[(int)$c['id']] ?? null) !== $c['statut']);
            $statuts[(int)$c['id']] = $c['statut'];
        }
        unset($c);
        notif_mark_seen($user['id'], $statuts);      // tout est marqué comme lu après affichage

        render('client/mes_commandes', ['title' => 'Notifications', 'commandes' => $commandes], 'notifications');
    }
}
