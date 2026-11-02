<?php
/**
 * QUEEN Cosmetics — point d'entrée (routeur)
 * URL : index.php?action=<route>
 *
 * ⚠ Si tu as déjà un index.php, garde le tien et ajoute seulement
 *   le bloc « ESPACE CLIENT » dans $routes (+ l'autoload et helpers.php).
 */
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/helpers.php';

spl_autoload_register(function (string $class): void {
    foreach (['controllers', 'models'] as $dir) {
        $file = APP_ROOT . "/app/$dir/$class.php";
        if (is_file($file)) { require_once $file; return; }
    }
});

$routes = [
    // ── ESPACE CLIENT ────────────────────────────────────────────
    'accueil'          => ['ClientController',   'accueil'],
    'panier'           => ['ClientController',   'panier'],
    'panier_ajouter'   => ['ClientController',   'panierAjouter'],
    'panier_modifier'  => ['ClientController',   'panierModifier'],
    'notifications'    => ['ClientController',   'notifications'],
    'confirmation'     => ['CommandeController', 'confirmation'],
    'commande_valider' => ['CommandeController', 'valider'],

    // ── AUTH (méthodes de TON AuthController) ───────────────────
    'login'            => ['AuthController',     'showLogin'],
    'login_post'       => ['AuthController',     'login'],
    'register'         => ['AuthController',     'showRegister'],
    'register_post'    => ['AuthController',     'register'],
    'logout'           => ['AuthController',     'logout'],
];

$page = $_GET['action'] ?? $_GET['page'] ?? 'accueil';
if (!isset($routes[$page])) {
    http_response_code(404);
    echo '<!DOCTYPE html><meta charset="utf-8"><title>404</title><p style="font:16px sans-serif;padding:40px">Page introuvable. <a href="' . e(url('accueil')) . '">Retour à l\'accueil</a></p>';
    exit;
}
[$controller, $method] = $routes[$page];
(new $controller())->$method();
