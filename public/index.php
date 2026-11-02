<?php
// public/index.php — point d'entree unique (routeur)
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers.php';

require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Produit.php';
require_once __DIR__ . '/../app/models/Commande.php';
require_once __DIR__ . '/../app/models/Paiement.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/ProduitController.php';
require_once __DIR__ . '/../app/controllers/CommandeController.php';
require_once __DIR__ . '/../app/controllers/PaiementController.php';

$controller = isset($_GET['controller']) && is_string($_GET['controller']) ? $_GET['controller'] : null;
$action     = isset($_GET['action'])     && is_string($_GET['action'])     ? $_GET['action']     : 'login';

// Anciennes actions (AuthController redirige encore vers elles apres le login).
$alias = [
    'produits_liste'   => ['produits', 'liste'],
    'commandes_gestion' => ['commandes', 'gestion'],
];
if ($controller === null && isset($alias[$action])) {
    [$controller, $action] = $alias[$action];
}

/* ---------- Routes MVC : index.php?controller=...&action=... ---------- */
if ($controller !== null) {
    // Liste blanche : seules ces actions peuvent etre appelees depuis l'URL.
    $routes = [
        'produits'  => [ProduitController::class,  ['liste', 'creer', 'modifier', 'supprimer', 'stock']],
        'commandes' => [CommandeController::class, ['gestion', 'accepter', 'refuser', 'livrer']],
        'paiements' => [PaiementController::class, ['gestion', 'confirmer']],
    ];

    if (!isset($routes[$controller]) || !in_array($action, $routes[$controller][1], true)) {
        http_response_code(404);
        exit('Page introuvable.');
    }

    $classe = $routes[$controller][0];
    (new $classe())->$action();
    exit;
}

/* ---------- Authentification et espaces client / admin ---------- */
$authController = new AuthController();

switch ($action) {
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

    case 'accueil':
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
            header('Location: index.php?action=login');
            exit;
        }
        require_once __DIR__ . '/../app/views/client/accueil.php';
        break;

    case 'admin_dashboard':
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit;
        }
        require_once __DIR__ . '/../app/views/admin/dashboard.php';
        break;

    default:
        $authController->showLogin();
        break;
}
