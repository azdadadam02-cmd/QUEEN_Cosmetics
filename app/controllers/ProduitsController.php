<?php defined('APP_ROOT') || exit;
/** Responsable produits : ajoute des produits (nom, catégorie, prix, stock, image) */
class ProduitsController
{
    public function liste(): void {
        require_role(['responsable_produits']);
        $old = $_SESSION['old_produit'] ?? [];
        unset($_SESSION['old_produit']);
        $model = new Produit();
        staff_render('produits/liste', [
            'title'      => 'Produits',
            'produits'   => $model->liste(),
            'categories' => $model->categories(),
            'old'        => $old + ['nom' => '', 'categorie' => '', 'prix' => '', 'stock' => '', 'promotion' => ''],
        ], 'produits_liste');
    }

    /** Valide les champs communs ajout / modification → [erreurs, données] */
    private function valider(): array {
        $nom   = trim($_POST['nom'] ?? '');
        $cat   = trim($_POST['categorie'] ?? '');
        $prix  = str_replace(',', '.', trim($_POST['prix'] ?? ''));
        $stock = trim($_POST['stock'] ?? '');
        $promo = trim($_POST['promotion'] ?? '0'); if ($promo === '') $promo = '0';
        $err   = [];
        if ($nom === '' || mb_strlen($nom, 'UTF-8') > 100)  $err[] = 'Le nom est obligatoire (100 caractères max).';
        if (mb_strlen($cat, 'UTF-8') > 50)                  $err[] = 'La catégorie ne doit pas dépasser 50 caractères.';
        if (!is_numeric($prix) || (float)$prix <= 0 || (float)$prix > 99999999) $err[] = 'Le prix doit être un nombre supérieur à 0.';
        if (!ctype_digit($stock) || (int)$stock > 1000000)  $err[] = 'La quantité en stock doit être un entier positif ou nul.';
        if (!ctype_digit($promo) || (int)$promo > 90)       $err[] = 'La promotion doit être un entier entre 0 et 90 (0 = aucune).';
        return [$err, ['nom' => $nom, 'cat' => $cat, 'prix' => (float)$prix, 'stock' => (int)$stock, 'promo' => (int)$promo, 'promo_brut' => $promo, 'prix_brut' => $_POST['prix'] ?? '', 'stock_brut' => $stock]];
    }

    /** POST — ajouter un produit */
    public function ajouter(): void {
        require_role(['responsable_produits']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('produits_liste');
        csrf_check();

        [$err, $d] = $this->valider();
        $image = null;
        if (!$err) {
            try { $image = $this->enregistrerImage($d['nom']); }
            catch (RuntimeException $e) { $err[] = $e->getMessage(); }
        }
        if ($err) {
            foreach ($err as $e) flash_add('error', $e);
            $_SESSION['old_produit'] = ['nom' => $d['nom'], 'categorie' => $d['cat'], 'prix' => $d['prix_brut'], 'stock' => $d['stock_brut'], 'promotion' => $d['promo_brut']];
            redirect('produits_liste');
        }
        try {
            (new Produit())->creer($d['nom'], $d['cat'], $d['prix'], $d['stock'], $image, $d['promo']);
            flash_add('success', "Produit « {$d['nom']} » ajouté au catalogue.");
        } catch (Throwable $e) {
            error_log('[QUEEN] ' . $e->getMessage());
            flash_add('error', "Erreur technique : le produit n'a pas été enregistré.");
        }
        redirect('produits_liste');
    }

    /** POST — modifier un produit (image optionnelle : sans fichier, l'ancienne est conservée) */
    public function modifier(): void {
        require_role(['responsable_produits']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('produits_liste');
        csrf_check();

        $id    = (int)($_POST['id'] ?? 0);
        $model = new Produit();
        if (!$model->trouver($id)) { flash_add('error', 'Produit introuvable.'); redirect('produits_liste'); }

        [$err, $d] = $this->valider();
        $image = null;
        if (!$err) {
            try { $image = $this->enregistrerImage($d['nom']); }
            catch (RuntimeException $e) { $err[] = $e->getMessage(); }
        }
        if ($err) { foreach ($err as $e) flash_add('error', $e); redirect('produits_liste'); }

        try {
            $model->modifier($id, $d['nom'], $d['cat'], $d['prix'], $d['stock'], $image, $d['promo']);
            flash_add('success', "Produit « {$d['nom']} » mis à jour.");
        } catch (Throwable $e) {
            error_log('[QUEEN] ' . $e->getMessage());
            flash_add('error', "Erreur technique : la modification a échoué.");
        }
        redirect('produits_liste');
    }

    /** Enregistre l'image envoyée dans public/images/ ; null si aucune image */
    private function enregistrerImage(string $nom): ?string {
        return upload_image('image', $nom);
    }
}
