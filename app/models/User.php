<?php defined('APP_ROOT') || exit;
// app/models/User.php — login / inscription (AuthController) + gestion des utilisateurs (admin)

class User
{
    /** Rôles que l'admin peut créer / modifier (jamais « admin ») */
    public const ROLES_GERABLES = ['client', 'responsable_produits', 'responsable_commandes'];

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
        return $this->creer($nom, $email, $password, 'client');
    }

    /* ───────────── Gestion par l'admin (tous sauf les admins) ───────────── */

    public function creer(string $nom, string $email, string $password, string $role): bool
    {
        $st = $this->db->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)');
        return $st->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    }

    /** Tous les utilisateurs SAUF admin, avec leur nombre de commandes */
    /** Met à jour le hash (rehash automatique à la connexion) */
    public function majHash(int $id, string $password): void
    {
        $st = $this->db->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $st->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function tousGerables(): array
    {
        return $this->db->query(
            "SELECT u.id, u.nom, u.email, u.role,
                    (SELECT COUNT(*) FROM commandes c WHERE c.client_id = u.id) AS nb_commandes
               FROM utilisateurs u
              WHERE u.role <> 'admin'
              ORDER BY u.id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouverGerable(int $id): ?array
    {
        $st = $this->db->prepare("SELECT id, nom, email, role FROM utilisateurs WHERE id = ? AND role <> 'admin'");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function emailExiste(string $email, int $sauf = 0): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM utilisateurs WHERE email = ? AND id <> ? LIMIT 1');
        $st->execute([$email, $sauf]);
        return (bool) $st->fetchColumn();
    }

    /** Mot de passe inchangé si $password est vide */
    public function modifier(int $id, string $nom, string $email, string $role, string $password = ''): bool
    {
        if ($password !== '') {
            $st = $this->db->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ?, mot_de_passe = ? WHERE id = ? AND role <> 'admin'");
            return $st->execute([$nom, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
        }
        $st = $this->db->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ? WHERE id = ? AND role <> 'admin'");
        return $st->execute([$nom, $email, $role, $id]);
    }

    public function aDesCommandes(int $id): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM commandes WHERE client_id = ? LIMIT 1');
        $st->execute([$id]);
        return (bool) $st->fetchColumn();
    }

    public function supprimer(int $id): bool
    {
        $st = $this->db->prepare("DELETE FROM utilisateurs WHERE id = ? AND role <> 'admin'");
        $st->execute([$id]);
        return $st->rowCount() > 0;
    }

    /** role => nombre (hors admin) */
    public function compteursParRole(): array
    {
        $out = [];
        foreach ($this->db->query("SELECT role, COUNT(*) AS n FROM utilisateurs WHERE role <> 'admin' GROUP BY role")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[$r['role']] = (int) $r['n'];
        }
        return $out;
    }
}
