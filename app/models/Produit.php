<?php
class Produit
{
    public static function all(): array
    {
        return Database::get()->query('SELECT * FROM produits ORDER BY id DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM produits WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function create(array $d): void
    {
        $st = Database::get()->prepare(
            'INSERT INTO produits (nom, categorie, prix, quantite_stock, image) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$d['nom'], $d['categorie'], $d['prix'], $d['quantite_stock'], $d['image']]);
    }

    public static function update(int $id, array $d): void
    {
        $st = Database::get()->prepare(
            'UPDATE produits SET nom = ?, categorie = ?, prix = ?, image = ? WHERE id = ?'
        );
        $st->execute([$d['nom'], $d['categorie'], $d['prix'], $d['image'], $id]);
    }

    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM produits WHERE id = ?')->execute([$id]);
    }

    public static function addStock(int $id, int $qte): void
    {
        Database::get()->prepare('UPDATE produits SET quantite_stock = quantite_stock + ? WHERE id = ?')
            ->execute([$qte, $id]);
    }

    public static function setStock(int $id, int $qte): void
    {
        Database::get()->prepare('UPDATE produits SET quantite_stock = ? WHERE id = ?')->execute([$qte, $id]);
    }

    public static function lowStock(): array
    {
        return Database::get()->query('SELECT * FROM produits WHERE quantite_stock < 5')->fetchAll();
    }
}