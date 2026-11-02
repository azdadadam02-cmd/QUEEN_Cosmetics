<?php
/**
 * app/models/Produit.php
 * ROLE    : parler à la table `produits` (la seule couche qui écrit du SQL pour les produits).
 * POURQUOI : MVC = chaque couche a un travail. Le contrôleur ne connaît pas SQL, il appelle
 *            Produit::all(), Produit::create()... Si la table change, on modifie ce seul fichier.
 *            Toutes les requêtes utilisent des `?` (requêtes préparées) : protection contre l'injection SQL.
 */
class Produit
{
    // Catégories autorisées (même valeurs que dans la base : 'Maquillage', 'Parfum', 'Soin').
    // POURQUOI : la colonne `categorie` est un VARCHAR libre, donc on contrôle la liste côté PHP.
    public const CATEGORIES = ['Maquillage', 'Parfum', 'Soin'];

    // Règle du cahier des charges : alerte quand le stock est inférieur à 5.
    public const SEUIL_STOCK_FAIBLE = 5;

    /** Liste de tous les produits (le plus récent d'abord). */
    public static function all(): array
    {
        return Database::get()
            ->query('SELECT * FROM produits ORDER BY id DESC')
            ->fetchAll();
    }

    /** Un seul produit par son id (null s'il n'existe pas). Sert pour la modification. */
    public static function find(int $id): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM produits WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Ajouter un produit. $d = nom, categorie, prix, quantite_stock, image. */
    public static function create(array $d): void
    {
        $st = Database::get()->prepare(
            'INSERT INTO produits (nom, categorie, prix, quantite_stock, image)
             VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$d['nom'], $d['categorie'], $d['prix'], $d['quantite_stock'], $d['image']]);
    }

    /**
     * Modifier un produit.
     * POURQUOI on ne touche PAS à quantite_stock ici : règle de gestion « le stock se modifie
     * uniquement via les fonctions de stock » (addStock / setStock), donc la modification d'une
     * fiche produit ne peut pas changer les quantités par accident.
     */
    public static function update(int $id, array $d): void
    {
        $st = Database::get()->prepare(
            'UPDATE produits SET nom = ?, categorie = ?, prix = ?, image = ? WHERE id = ?'
        );
        $st->execute([$d['nom'], $d['categorie'], $d['prix'], $d['image'], $id]);
    }

    /** Supprimer un produit (échoue si le produit est déjà dans une commande : clé étrangère). */
    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM produits WHERE id = ?')->execute([$id]);
    }

    /**
     * AJOUTER une quantité au stock actuel (ex : livraison fournisseur de 10 pièces).
     * POURQUOI `quantite_stock = quantite_stock + ?` en SQL : le calcul est fait par MySQL,
     * donc pas de risque d'écraser une valeur lue trop tôt si deux personnes agissent en même temps.
     */
    public static function addStock(int $id, int $qte): void
    {
        Database::get()
            ->prepare('UPDATE produits SET quantite_stock = quantite_stock + ? WHERE id = ?')
            ->execute([$qte, $id]);
    }

    /** FIXER le stock à une valeur précise (ex : après un inventaire). */
    public static function setStock(int $id, int $qte): void
    {
        Database::get()
            ->prepare('UPDATE produits SET quantite_stock = ? WHERE id = ?')
            ->execute([$qte, $id]);
    }

    /** Produits dont le stock est faible (< 5) : alimente l'alerte du responsable. */
    public static function lowStock(): array
    {
        $st = Database::get()->prepare(
            'SELECT * FROM produits WHERE quantite_stock < ? ORDER BY quantite_stock'
        );
        $st->execute([self::SEUIL_STOCK_FAIBLE]);
        return $st->fetchAll();
    }
}