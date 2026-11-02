<?php
// app/controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';

class AuthController {
    private ?User $userModel = null;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Kanjibo User Model ghir mnin kan7tajoh f POST (Login/Register)
    private function getUserModel(): User {
        if ($this->userModel === null) {
            $this->userModel = new User();
        }
        return $this->userModel;
    }

    public function showLogin(): void {
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function showRegister(): void {
        require_once __DIR__ . '/../views/auth/register.php';
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=login');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $errors   = [];

        if ($email === '') {
            $errors['email'] = "L'adresse email est obligatoire.";
        } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Veuillez saisir une adresse email valide.";
        }

        if ($password === '') {
            $errors['mot_de_passe'] = "Le mot de passe est obligatoire.";
        } elseif (strlen($password) < 8) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins une lettre majuscule.";
        }

        if (!empty($errors)) {
            $_SESSION['errors']    = $errors;
            $_SESSION['old_email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            header('Location: index.php?action=login');
            exit;
        }

        $user = $this->getUserModel()->findByEmail($email);

        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['auth_error'] = "Adresse email ou mot de passe incorrect.";
            $_SESSION['old_email']  = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            header('Location: index.php?action=login');
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int) $user['id'],
            'nom'   => $user['nom'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];

        $this->redirectByRole($user['role']);
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=register');
            exit;
        }

        $nom      = trim($_POST['nom'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $errors   = [];

        if ($nom === '') {
            $errors['nom'] = "Le nom complet est obligatoire.";
        } elseif (mb_strlen($nom, 'UTF-8') <= 3) {
            $errors['nom'] = "Le nom doit contenir strictement plus de 3 caractères.";
        } elseif (mb_strlen($nom, 'UTF-8') > 100) {
            $errors['nom'] = "Le nom ne doit pas dépasser 100 caractères.";
        }

        if ($email === '') {
            $errors['email'] = "L'adresse email est obligatoire.";
        } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Format d'adresse email invalide.";
        } elseif ($this->getUserModel()->findByEmail($email)) {
            $errors['email'] = "Cette adresse email est déjà associée à un compte.";
        }

        if ($password === '') {
            $errors['mot_de_passe'] = "Le mot de passe est obligatoire.";
        } elseif (strlen($password) < 8) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins une lettre majuscule.";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = [
                'nom'   => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
            ];
            header('Location: index.php?action=register');
            exit;
        }

        if ($this->getUserModel()->createClient($nom, $email, $password)) {
            // Connexion automatique après l'inscription → direct à l'accueil
            $user = $this->getUserModel()->findByEmail($email);
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'    => (int) $user['id'],
                    'nom'   => $user['nom'],
                    'email' => $user['email'],
                    'role'  => $user['role']
                ];
                header('Location: index.php?action=accueil');
                exit;
            }
            $_SESSION['success_msg'] = "Votre compte client a été créé avec succès ! Connectez-vous maintenant.";
            header('Location: index.php?action=login');
            exit;
        }

        $_SESSION['auth_error'] = "Une erreur technique est survenue. Veuillez réessayer.";
        header('Location: index.php?action=register');
        exit;
    }

    private function redirectByRole(string $role): void {
        switch ($role) {
            case 'admin':
                header('Location: index.php?action=admin_dashboard');
                break;
            case 'responsable_produits':
                header('Location: index.php?action=produits_liste');
                break;
            case 'responsable_commandes':
                header('Location: index.php?action=commandes_gestion');
                break;
            case 'client':
            default:
                header('Location: index.php?action=accueil');
                break;
        }
        exit;
    }

    public function logout(): void {
        $_SESSION = [];
        session_unset();
        session_destroy();

        // Reste sur la page actuelle (sauf pages réservées aux clients connectés → accueil)
        $target = 'index.php?action=accueil';
        $ref    = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === ($_SERVER['SERVER_NAME'] ?? null)) {
            parse_str((string) parse_url($ref, PHP_URL_QUERY), $q);
            $act = $q['action'] ?? 'accueil';
            if (!in_array($act, ['notifications', 'confirmation', 'commande_valider', 'logout', 'login_post', 'register_post'], true)) {
                $target = 'index.php?action=' . rawurlencode($act);
            }
        }
        header('Location: ' . $target);
        exit;
    }
}