<?php defined('APP_ROOT') || exit;
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
        $old = $_SESSION['old_livraison'] ?? [];
        unset($_SESSION['old_livraison']);
        $old += ['nom' => $user['nom'], 'telephone' => '', 'ville' => '', 'adresse' => ''];
        render('client/confirmation', ['title' => 'Confirmation', 'panier' => $panier, 'user' => $user, 'old' => $old], 'panier');
    }

    /** POST — crée la commande (paiement à la livraison) */
    public function valider(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('panier');
        csrf_check();

        $user = current_user();
        if (!$user) redirect('login');

        if ((new Commande())->nbEnAttente((int)$user['id']) >= 5) {
            flash_add('error', 'Vous avez déjà 5 commandes en attente. Patientez leur traitement avant d\'en passer une nouvelle.');
            redirect('notifications');
        }
        if (throttle_count('commande|' . (int)$user['id'], 3600) >= 10) {
            audit('commande_limite', 'user=' . (int)$user['id']);
            flash_add('error', 'Trop de commandes en peu de temps. Réessayez plus tard.');
            redirect('panier');
        }

        $panier = Panier::lignes();
        if (!$panier['lignes']) {
            flash_add('info', 'Votre panier est vide.');
            redirect('panier');
        }
        // Informations de livraison
        $liv = [
            'nom'       => trim($_POST['nom'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'ville'     => trim($_POST['ville'] ?? ''),
            'adresse'   => trim($_POST['adresse'] ?? ''),
        ];
        $tel = preg_replace('/[\s.\-]/', '', $liv['telephone']);
        $err = [];
        if (empty($_POST['majeur'])) $err[] = 'Vous devez confirmer avoir 18 ans ou plus pour passer commande.';
        if (mb_strlen($liv['nom'], 'UTF-8') < 3 || mb_strlen($liv['nom'], 'UTF-8') > 100)       $err[] = 'Indiquez le nom de la personne qui réceptionne (3 à 100 caractères).';
        if (!preg_match('/^(\+212|00212|0)[5-7]\d{8}$/', $tel))                                   $err[] = 'Numéro de téléphone invalide (ex : 06 12 34 56 78).';
        if (mb_strlen($liv['ville'], 'UTF-8') < 2 || mb_strlen($liv['ville'], 'UTF-8') > 80)      $err[] = 'Indiquez votre ville.';
        if (mb_strlen($liv['adresse'], 'UTF-8') < 8 || mb_strlen($liv['adresse'], 'UTF-8') > 255) $err[] = "Indiquez votre adresse complète (rue, n°, quartier — 8 caractères minimum).";
        if ($err) {
            foreach ($err as $m) flash_add('error', $m);
            $_SESSION['old_livraison'] = $liv;
            redirect('confirmation');
        }
        $liv['telephone'] = $tel;

        try {
            $id = (new Commande())->creer($user['id'], $panier['lignes'], $liv);
            throttle_hit("commande|" . (int)$user["id"]);
            Panier::vider();
            flash_add('success', "Commande #$id enregistrée — vous réglerez à la livraison.");
            redirect('notifications');
        } catch (RuntimeException $e) {
            flash_add('error', $e->getMessage());
            redirect('panier');
        }
    }
}
