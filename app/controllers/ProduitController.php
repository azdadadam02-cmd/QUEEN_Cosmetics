<?php
/**
 * app/controllers/ProduitController.php
 * ROLE    : recevoir les actions du responsable produits & stock, vérifier les données,
 *           appeler le modèle Produit, puis afficher une vue ou rediriger.
 * POURQUOI : le contrôleur est le « chef d'orchestre » du MVC : la vue ne parle jamais à la base,
 *            le modèle ne connaît pas le HTML. Tout passe par ici.
 *
 * Pattern utilisé partout : GET = afficher le formulaire / POST = traiter puis rediriger
 * (redirection après POST = si l'utilisateur rafraîchit la page, l'action n'est pas refaite).
 */
class ProduitController
{
    /** Page 1 : liste des produits (consultation + boutons Modifier / Supprimer). */
    public function liste(): void
    {
        // Seul le responsable produits entre ici (règle de gestion : lui seul gère produits et stock).
        require_role('responsable_produits');

        render('produits/liste', [
            'titre'    => 'Liste des produits',
            'produits' => Produit::all(),
        ]);
    }

    /** Page 2 : ajouter un produit. */
    public function creer(): void
    {
        require_role('responsable_produits');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf(); // refuse un formulaire envoyé depuis un autre site
            $d = $this->valider($_POST);
            if ($d === null) {                       // données invalides => on garde la saisie et on revient
                $_SESSION['old'] = $_POST;
                redirect('produits', 'creer');
            }
            $qte = filter_var($_POST['quantite_stock'] ?? '', FILTER_VALIDATE_INT);
            if ($qte === false || $qte < 0) {
                flash('error', 'Quantité invalide.');
                $_SESSION['old'] = $_POST;
                redirect('produits', 'creer');
            }
            $d['quantite_stock'] = $qte;
            try {
                $d['image'] = upload_image('image'); // null si aucune image envoyée
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('produits', 'creer');
            }
            Produit::create($d);
            flash('success', 'Produit ajouté.');
            redirect('produits', 'liste');
        }

        // GET : afficher le formulaire (en reprenant la saisie précédente s'il y a eu une erreur)
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        render('produits/formulaire', ['titre' => 'Ajouter un produit', 'produit' => $old, 'edition' => false]);
    }

    /** Page 3 : modifier un produit (nom, catégorie, prix, image — pas le stock). */
    public function modifier(): void
    {
        require_role('responsable_produits');

        $id = (int)($_GET['id'] ?? 0);
        $produit = Produit::find($id);
        if (!$produit) {
            flash('error', 'Produit introuvable.');
            redirect('produits', 'liste');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $d = $this->valider($_POST);
            if ($d === null) redirect('produits', 'modifier', ['id' => $id]);
            try {
                $nouvelle = upload_image('image');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('produits', 'modifier', ['id' => $id]);
            }
            // Si aucune nouvelle image n'est envoyée, on garde l'ancienne.
            $d['image'] = $nouvelle ?? $produit['image'];
            Produit::update($id, $d);
            if ($nouvelle) delete_image($produit['image']); // on supprime l'ancien fichier devenu inutile
            flash('success', 'Produit modifié.');
            redirect('produits', 'liste');
        }

        render('produits/formulaire', ['titre' => 'Modifier le produit', 'produit' => $produit, 'edition' => true]);
    }

    /** Supprimer un produit (POST uniquement, jamais par simple lien). */
    public function supprimer(): void
    {
        require_role('responsable_produits');
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $p  = Produit::find($id);
        if ($p) {
            try {
                Produit::delete($id);
                delete_image($p['image']);
                flash('success', 'Produit supprimé.');
            } catch (PDOException $e) {
                // La clé étrangère de details_commandes bloque la suppression d'un produit déjà commandé.
                flash('error', 'Impossible de supprimer : ce produit apparaît dans des commandes. Mettez son stock à 0.');
            }
        }
        redirect('produits', 'liste');
    }

    /**
     * Page 4 : gestion du stock.
     * GET  = afficher les quantités + l'alerte « stock faible ».
     * POST = ajouter une quantité (mode=ajouter) ou fixer la quantité (mode=fixer).
     */
    public function stock(): void
    {
        require_role('responsable_produits');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $id   = (int)($_POST['id'] ?? 0);
            $mode = $_POST['mode'] ?? '';
            $qte  = filter_var($_POST['quantite'] ?? '', FILTER_VALIDATE_INT);

            if (!Produit::find($id) || $qte === false || $qte < 0 || $qte > 100000) {
                flash('error', 'Produit ou quantité invalide.');
            } elseif ($mode === 'ajouter') {
                Produit::addStock($id, $qte);
                flash('success', "Stock augmenté de $qte.");
            } elseif ($mode === 'fixer') {
                Produit::setStock($id, $qte);
                flash('success', 'Quantité mise à jour.');
            }
            redirect('produits', 'stock');
        }

        render('produits/stock', [
            'titre'    => 'Gestion du stock',
            'produits' => Produit::all(),
            'alertes'  => Produit::lowStock(),
        ]);
    }

    /**
     * Validation commune à « ajouter » et « modifier ».
     * POURQUOI côté serveur : les attributs HTML (required, min) se contournent facilement.
     * Retourne les données propres, ou null (avec un message d'erreur) si quelque chose est faux.
     */
    private function valider(array $in): ?array
    {
        $nom  = trim($in['nom'] ?? '');
        $cat  = $in['categorie'] ?? '';
        $prix = filter_var(str_replace(',', '.', $in['prix'] ?? ''), FILTER_VALIDATE_FLOAT);

        if ($nom === '' || mb_strlen($nom) > 100) { flash('error', 'Nom invalide (1 à 100 caractères).'); return null; }
        if (!in_array($cat, Produit::CATEGORIES, true)) { flash('error', 'Catégorie invalide.'); return null; }
        if ($prix === false || $prix < 0 || $prix > 1000000) { flash('error', 'Prix invalide.'); return null; }

        return ['nom' => $nom, 'categorie' => $cat, 'prix' => $prix];
    }
}