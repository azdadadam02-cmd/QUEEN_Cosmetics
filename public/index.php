<?php
// public/index.php
session_start();

require_once __DIR__ . '/../app/controllers/AuthController.php';

$action = $_GET['action'] ?? 'login';
$authController = new AuthController();

switch ($action) {
    // Pages d'authentification
    case 'login':
        $authController->showLogin();
        break;

    case 'login_post':
        $authController->login();
        break;

    case 'register':
        $authController->showRegister();
        break;

    case 'register_post':
        $authController->register();
        break;

    case 'logout':
        $authController->logout();
        break;

    // Espace Client
    case 'accueil':
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
            header('Location: index.php?action=login');
            exit;
        }
        require_once __DIR__ . '/../app/views/client/accueil.php';
        break;

    // Espace Admin
    case 'admin_dashboard':
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit;
        }
        require_once __DIR__ . '/../app/views/admin/dashboard.php';
        break;

    // Espace Responsable Produits
    case 'produits_liste':
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['responsable_produits', 'admin'])) {
            header('Location: index.php?action=login');
            exit;
        }
        require_once __DIR__ . '/../app/views/produits/liste.php';
        break;

    // Espace Responsable Commandes
    case 'commandes_gestion':
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['responsable_commandes', 'admin'])) {
            header('Location: index.php?action=login');
            exit;
        }
        require_once __DIR__ . '/../app/views/commandes/gestion.php';
        break;

    default:
        $authController->showLogin();
        break;
}