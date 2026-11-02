<?php
// app/models/User.php
// ROLE : parler a la table `utilisateurs` (connexion et inscription des clients).

require_once __DIR__ . '/../../config/database.php';

class User
{
    /** Retourne l'utilisateur ayant cet email, ou null. */
    public function findByEmail(string $email): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM utilisateurs WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    /** Cree un compte CLIENT (le mot de passe est hache, jamais stocke en clair). */
    public function createClient(string $nom, string $email, string $password): bool
    {
        try {
            $st = Database::get()->prepare(
                "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'client')"
            );
            return $st->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
