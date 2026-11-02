<?php defined('APP_ROOT') || exit;
/**
 * Admin : consulte produits / stock / commandes (lecture seule)
 *         et gère tous les utilisateurs SAUF les comptes admin.
 */
class AdminController
{
    public function dashboard(): void {
        require_role(['admin']);
        $cmd   = (new Commande())->compteurs();
        $roles = (new User())->compteursParRole();
        $fin   = (new Finance())->resume();
        staff_render('admin/dashboard', [
            'title'    => 'Tableau de bord',
            'produits' => (new Produit())->stats(),
            'cmd'      => $cmd,
            'cmdTotal' => array_sum($cmd),
            'roles'    => $roles,
            'usersTotal' => array_sum($roles),
            'fin'      => $fin,
        ], 'admin_dashboard');
    }

    public function produits(): void {
        require_role(['admin']);
        $model = new Produit();
        staff_render('admin/produits', [
            'title'    => 'Produits & stock',
            'produits' => $model->liste(),
            'stats'    => $model->stats(),
        ], 'admin_produits');
    }

    public function commandes(): void {
        require_role(['admin']);
        $filtre = $_GET['statut'] ?? '';
        if (!in_array($filtre, ['En attente', 'Acceptee', 'Livree', 'Refusee'], true)) $filtre = '';
        $model = new Commande();
        staff_render('admin/commandes', [
            'title'     => 'Commandes',
            'commandes' => $model->toutes($filtre ?: null),
            'compteurs' => $model->compteurs(),
            'filtre'    => $filtre,
        ], 'admin_commandes');
    }

    public function finances(): void {
        require_role(['admin']);
        $filtre = $_GET['filtre'] ?? '';
        if (!in_array($filtre, ['paye', 'encaisser'], true)) $filtre = '';
        $f = new Finance();
        staff_render('admin/finances', [
            'title'     => 'Finances',
            'resume'    => $f->resume(),
            'jours'     => $f->parJour(14),
            'paiements' => $f->paiements($filtre),
            'filtre'    => $filtre,
        ], 'admin_finances');
    }

    public function utilisateurs(): void {
        require_role(['admin']);
        staff_render('admin/utilisateurs', [
            'title'  => 'Utilisateurs',
            'users'  => (new User())->tousGerables(),
        ], 'admin_utilisateurs');
    }

    /** POST — créer (id = 0) ou modifier un utilisateur non-admin */
    public function utilisateurSave(): void {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_utilisateurs');
        csrf_check();

        $id    = (int)($_POST['id'] ?? 0);
        $nom   = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? '';
        $pw    = $_POST['mot_de_passe'] ?? '';
        $m     = new User();
        $err   = [];

        if (mb_strlen($nom, 'UTF-8') <= 3)   $err[] = 'Le nom doit contenir plus de 3 caractères.';
        if (mb_strlen($nom, 'UTF-8') > 100)  $err[] = 'Le nom ne doit pas dépasser 100 caractères.';
        if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Adresse email invalide.';
        elseif ($m->emailExiste($email, $id))  $err[] = 'Cette adresse email est déjà utilisée.';
        if (!in_array($role, User::ROLES_GERABLES, true)) $err[] = 'Rôle invalide.';
        if ($id === 0 || $pw !== '') {
            if (strlen($pw) < 8)                $err[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            elseif (!preg_match('/[A-Z]/', $pw)) $err[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if ($id > 0 && !$m->trouverGerable($id)) $err[] = 'Utilisateur introuvable (les comptes admin ne sont pas modifiables).';

        if ($err) { foreach ($err as $e) flash_add('error', $e); redirect('admin_utilisateurs'); }

        try {
            if ($id === 0) { $m->creer($nom, $email, $pw, $role);               flash_add('success', "Utilisateur « $nom » créé."); }
            else           { $m->modifier($id, $nom, $email, $role, $pw);       flash_add('success', "Utilisateur « $nom » mis à jour."); }
        } catch (Throwable $e) {
            error_log('[QUEEN] ' . $e->getMessage());
            flash_add('error', "Erreur technique : l'enregistrement a échoué.");
        }
        redirect('admin_utilisateurs');
    }

    /** POST — supprimer (refusé si l'utilisateur a des commandes) */
    public function utilisateurSupprimer(): void {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_utilisateurs');
        csrf_check();

        $id = (int)($_POST['id'] ?? 0);
        $m  = new User();
        $u  = $m->trouverGerable($id);
        if (!$u)                      flash_add('error', 'Utilisateur introuvable (les comptes admin ne sont pas supprimables).');
        elseif ($m->aDesCommandes($id)) flash_add('error', "Suppression impossible : « {$u['nom']} » a déjà passé des commandes.");
        else {
            try { $m->supprimer($id); flash_add('success', "Utilisateur « {$u['nom']} » supprimé."); }
            catch (Throwable $e) { error_log('[QUEEN] ' . $e->getMessage()); flash_add('error', 'Suppression impossible.'); }
        }
        redirect('admin_utilisateurs');
    }

    /* ───────────── Publicités (bandeau de l'accueil client) ───────────── */

    public function publicites(): void {
        require_role(['admin']);
        staff_render('admin/publicites', [
            'title' => 'Publicités',
            'pubs'  => (new Publicite())->toutes(),
            'produits' => (new Produit())->liste(),
        ], 'admin_publicites');
    }

    /** POST — créer (id = 0) ou modifier une publicité */
    public function publiciteSave(): void {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_publicites');
        csrf_check();

        $id    = (int)($_POST['id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $red   = trim($_POST['reduction'] ?? '0');
        $m     = new Publicite();
        $err   = [];
        $pid   = (int)($_POST['produit_id'] ?? 0);
        if ($pid > 0 && !(new Produit())->trouver($pid)) $err[] = 'Produit introuvable.';
        if ($pid > 0 && (int)($red === '' ? 0 : (ctype_digit($red) ? $red : 0)) === 0) $err[] = 'Pour lier un produit, indiquez une réduction supérieure à 0.';

        if ($titre === '' || mb_strlen($titre, 'UTF-8') > 100) $err[] = 'Le titre est obligatoire (100 caractères max).';
        if ($red === '') $red = '0';
        if (!ctype_digit($red) || (int)$red > 90)              $err[] = 'La réduction doit être un entier entre 0 et 90 (0 = pas de badge).';
        if ($id > 0 && !$m->trouver($id))                      $err[] = 'Publicité introuvable.';

        $image = null;
        if (!$err) {
            try { $image = upload_image('image', $titre); }
            catch (RuntimeException $e) { $err[] = $e->getMessage(); }
        }
        if ($err) { foreach ($err as $e) flash_add('error', $e); redirect('admin_publicites'); }

        try {
            if ($id === 0) { $m->creer($titre, (int)$red, $image, $pid);       flash_add('success', "Publicité « $titre » ajoutée : elle défile déjà sur l'accueil."); }
            else           { $m->modifier($id, $titre, (int)$red, $image, $pid);      flash_add('success', "Publicité « $titre » mise à jour."); }
        } catch (Throwable $e) {
            error_log('[QUEEN] ' . $e->getMessage());
            flash_add('error', "Erreur technique : l'enregistrement a échoué.");
        }
        redirect('admin_publicites');
    }

    /** POST — supprimer une publicité */
    public function publiciteSupprimer(): void {
        require_role(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_publicites');
        csrf_check();

        $id = (int)($_POST['id'] ?? 0);
        $m  = new Publicite();
        if (!($p = $m->trouver($id))) { flash_add('error', 'Publicité introuvable.'); }
        else { try { $m->supprimer($id); flash_add('success', "Publicité « {$p['titre']} » supprimée."); }
               catch (Throwable $e) { error_log('[QUEEN] ' . $e->getMessage()); flash_add('error', 'Suppression impossible.'); } }
        redirect('admin_publicites');
    }
}
