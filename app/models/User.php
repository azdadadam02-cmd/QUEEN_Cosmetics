<?php
// app/models/User.php — utilisé par AuthController (login / inscription client)

class User
{
    private PDO $db;

    public function __construct()
    {
        // db() vient de app/helpers.php (config/database.php doit retourner un PDO)
        $this->db = db();
    }

    /** Retourne la ligne utilisateur (avec mot_de_passe hashé) ou false */
    public function findByEmail(string $email)
    {
        $st = $this->db->prepare('SELECT * FROM utilisateurs WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    /** Crée un compte client ; mot de passe hashé avec password_hash */
    public function createClient(string $nom, string $email, string $password): bool
    {
        $st = $this->db->prepare(
            "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'client')"
        );
        return $st->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);
    }
}
