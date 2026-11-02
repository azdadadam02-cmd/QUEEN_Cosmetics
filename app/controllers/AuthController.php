<?php
defined('APP_ROOT') || exit;
// app/controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';

class AuthController {
    private ?User $userModel = null;

    /** Hash factice : on fait toujours un password_verify, même si l'email n'existe pas (anti « timing attack ») */
    private const DUMMY_HASH = '$2y$10$qjKPveIDf69qRnmRWEJVcOHQgONCKzSB0q7qw7BsLZleCFF9C2z2q';

    private const LOGIN_MAX_PAR_EMAIL = 5;     // échecs / 15 min pour (email + IP)
    private const LOGIN_MAX_PAR_IP    = 30;    // échecs / 15 min pour une IP
    private const LOGIN_FENETRE       = 900;
    private const INSCRIPTIONS_MAX    = 10;    // comptes créés / heure / IP

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

    private function back(string $action): void {
        header('Location: index.php?action=' . $action);
        exit;
    }

    public function showLogin(): void {
        if ($u = current_user()) { $this->redirectByRole($u['role']); }   // déjà connecté
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function showRegister(): void {
        if ($u = current_user()) { $this->redirectByRole($u['role']); }
        require_once __DIR__ . '/../views/auth/register.php';
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->back('login'); }

        if (!csrf_valid()) {
            audit('csrf_echec', 'login');
            $_SESSION['auth_error'] = "Session expirée, veuillez réessayer.";
            $this->back('login');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $errors   = [];

        if (!is_string($password) || !is_string($email)) { $this->back('login'); }

        if ($email === '') {
            $errors['email'] = "L'adresse email est obligatoire.";
        } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Veuillez saisir une adresse email valide.";
        }

        if ($password === '') {
            $errors['mot_de_passe'] = "Le mot de passe est obligatoire.";
        } elseif (strlen($password) < 8) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (strlen($password) > 72) {
            $errors['mot_de_passe'] = "Le mot de passe ne doit pas dépasser 72 caractères.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins une lettre majuscule.";
        }

        if (!empty($errors)) {
            $_SESSION['errors']    = $errors;
            $_SESSION['old_email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $this->back('login');
        }

        // ── Anti brute-force : 5 échecs / 15 min pour (email + IP), 30 pour une IP ──
        $ip     = client_ip();
        $kEmail = 'login|' . strtolower($email) . '|' . $ip;
        $kIp    = 'loginip|' . $ip;
        if (throttle_count($kEmail, self::LOGIN_FENETRE) >= self::LOGIN_MAX_PAR_EMAIL
         || throttle_count($kIp,    self::LOGIN_FENETRE) >= self::LOGIN_MAX_PAR_IP) {
            audit('login_bloque', $email);
            $_SESSION['auth_error'] = "Trop de tentatives échouées. Patientez 15 minutes avant de réessayer.";
            $_SESSION['old_email']  = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $this->back('login');
        }

        $user = $this->getUserModel()->findByEmail($email);
        $ok   = password_verify($password, $user['mot_de_passe'] ?? self::DUMMY_HASH) && $user;

        if (!$ok) {
            throttle_hit($kEmail);
            throttle_hit($kIp);
            audit('login_echec', $email);
            $_SESSION['auth_error'] = "Adresse email ou mot de passe incorrect.";
            $_SESSION['old_email']  = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $this->back('login');
        }

        throttle_clear($kEmail);
        if (password_needs_rehash($user['mot_de_passe'], PASSWORD_DEFAULT)) {
            $this->getUserModel()->majHash((int)$user['id'], $password);
        }

        session_login($user);                    // nouvel ID de session + jeton CSRF neuf + horodatages
        audit('login_ok', $user['email'] . ' role=' . $user['role']);

        $this->redirectByRole($user['role']);
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->back('register'); }

        if (!csrf_valid()) {
            audit('csrf_echec', 'register');
            $_SESSION['auth_error'] = "Session expirée, veuillez réessayer.";
            $this->back('register');
        }

        $nom      = trim($_POST['nom'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $errors   = [];

        if (!is_string($nom) || !is_string($email) || !is_string($password)) { $this->back('register'); }

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
        } elseif (strlen($password) > 72) {
            $errors['mot_de_passe'] = "Le mot de passe ne doit pas dépasser 72 caractères.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins une lettre majuscule.";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = [
                'nom'   => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
            ];
            $this->back('register');
        }

        // ── Anti création de comptes en masse ──
        $kReg = 'register|' . client_ip();
        if (throttle_count($kReg, 3600) >= self::INSCRIPTIONS_MAX) {
            audit('inscription_bloquee', $email);
            $_SESSION['auth_error'] = "Trop de créations de compte depuis votre connexion. Réessayez plus tard.";
            $this->back('register');
        }

        if ($this->getUserModel()->createClient($nom, $email, $password)) {
            throttle_hit($kReg);
            audit('inscription', $email);
            // Connexion automatique après l'inscription → direct à l'accueil
            $user = $this->getUserModel()->findByEmail($email);
            if ($user) {
                session_login($user);
                header('Location: index.php?action=accueil');
                exit;
            }
            $_SESSION['success_msg'] = "Votre compte client a été créé avec succès ! Connectez-vous maintenant.";
            $this->back('login');
        }

        $_SESSION['auth_error'] = "Une erreur technique est survenue. Veuillez réessayer.";
        $this->back('register');
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

    /** Déconnexion : POST + jeton CSRF obligatoires (un simple lien/image ne peut pas déconnecter l'utilisateur) */
    public function logout(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $this->back('accueil');
        }

        // Reste sur la page actuelle si elle est publique, sinon accueil
        $target = 'accueil';
        $ref    = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '' && strcasecmp((string)parse_url($ref, PHP_URL_HOST), (string)parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) === 0) {
            parse_str((string)parse_url($ref, PHP_URL_QUERY), $q);
            if (($q['action'] ?? '') === 'panier') { $target = 'panier'; }
        }

        audit('deconnexion');
        session_logout();
        $this->back($target);
    }
}
