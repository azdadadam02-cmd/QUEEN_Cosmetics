<?php
class ProduitController
{
    // 1. Afficher la liste + alerte stock faible
    public function liste(): void
    {
        require_role('responsable_produits');
        render('produits/liste', [
            'titre'    => 'Produits et stock',
            'produits' => Produit::all(),
            'alertes'  => Produit::lowStock(),
        ]);
    }

    // 2. Ajouter un produit (GET = formulaire, POST = enregistrer)
    public function creer(): void
    {
        require_role('responsable_produits');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $d = $this->valider($_POST);
            if (!$d) redirect('produits', 'creer');
            $d['quantite_stock'] = (int)$_POST['quantite_stock'];
            $d['image'] = upload_image('image');
            Produit::create($d);
            flash('success', 'Produit ajouté.');
            redirect('produits', 'liste');
        }
        render('produits/formulaire', ['titre' => 'Ajouter un produit', 'produit' => [], 'edition' => false]);
    }

    // 3. Modifier un produit
    public function modifier(): void
    {
        require_role('responsable_produits');
        $id = (int)($_GET['id'] ?? 0);
        $produit = Produit::find($id);
        if (!$produit) redirect('produits', 'liste');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $d = $this->valider($_POST);
            if (!$d) redirect('produits', 'modifier', ['id' => $id]);
            $d['image'] = upload_image('image') ?? $produit['image'];
            Produit::update($id, $d);
            flash('success', 'Produit modifié.');
            redirect('produits', 'liste');
        }
        render('produits/formulaire', ['titre' => 'Modifier le produit', 'produit' => $produit, 'edition' => true]);
    }

    // 4. Supprimer
    public function supprimer(): void
    {
        require_role('responsable_produits');
        verify_csrf();
        try {
            Produit::delete((int)$_POST['id']);
            flash('success', 'Produit supprimé.');
        } catch (PDOException $e) {
            flash('error', 'Impossible : ce produit existe dans des commandes.');
        }
        redirect('produits', 'liste');
    }

    // 5. Gérer le stock : mode = ajouter | fixer
    public function stock(): void
    {
        require_role('responsable_produits');
        verify_csrf();
        $id  = (int)$_POST['id'];
        $qte = filter_var($_POST['quantite'] ?? '', FILTER_VALIDATE_INT);
        if ($qte === false || $qte < 0) {
            flash('error', 'Quantité invalide.');
        } elseif (($_POST['mode'] ?? '') === 'ajouter') {
            Produit::addStock($id, $qte);
            flash('success', 'Stock augmenté.');
        } else {
            Produit::setStock($id, $qte);
            flash('success', 'Stock mis à jour.');
        }
        redirect('produits', 'liste');
    }

    // Validation commune (retourne null si erreur)
    private function valider(array $in): ?array
    {
        $nom  = trim($in['nom'] ?? '');
        $prix = filter_var($in['prix'] ?? '', FILTER_VALIDATE_FLOAT);
        if ($nom === '' || $prix === false || $prix < 0) {
            flash('error', 'Nom ou prix invalide.');
            return null;
        }
        return ['nom' => $nom, 'categorie' => trim($in['categorie'] ?? ''), 'prix' => $prix];
    }
}