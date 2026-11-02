<?php
/**
 * QUEEN Cosmetics — point d'entrée unique (routeur sécurisé)
 * URL : index.php?action=<route>
 *
 * Chaque route déclare :  [Contrôleur, méthode, verbes HTTP autorisés, accès]
 *   accès :  null              → public
 *            ['client', …]     → connecté ET rôle dans la liste (sinon refus + journal)
 * Tout ce qui n'est pas dans cette liste blanche → 404.
 *
 * ⚠ Si tu gardes ton propre index.php : copie ce tableau $routes + le bloc « DISPATCH »,
 *   et garde `require app/helpers.php` tout en haut (c'est lui qui active la sécurité).
 */
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/helpers.php';

spl_autoload_register(function (string $class): void {
    if (!preg_match('/^[A-Za-z]+$/', $class)) return;            // jamais de chemin dans un nom de classe
    foreach (['controllers', 'models'] as $dir) {
        $file = APP_ROOT . "/app/$dir/$class.php";
        if (is_file($file)) { require_once $file; return; }
    }
});

$G = 'GET'; $P = 'POST';
$routes = [
    'langue'           => ['LangController',     'changer',        $G, null],

    // ── BOUTIQUE (public) ────────────────────────────────────────
    'accueil'          => ['ClientController',   'accueil',        $G, null],
    'panier'           => ['ClientController',   'panier',         $G, null],
    'panier_ajouter'   => ['ClientController',   'panierAjouter',  $P, null],
    'panier_modifier'  => ['ClientController',   'panierModifier', $P, null],

    // ── ESPACE CLIENT (compte client uniquement) ────────────────
    'notifications'    => ['ClientController',   'notifications',  $G, ['client']],
    'confirmation'     => ['CommandeController', 'confirmation',   $G, ['client']],
    'commande_valider' => ['CommandeController', 'valider',        $P, ['client']],

    // ── ADMIN ────────────────────────────────────────────────────
    'admin_dashboard'             => ['AdminController', 'dashboard',           $G, ['admin']],
    'admin_produits'              => ['AdminController', 'produits',            $G, ['admin']],
    'admin_commandes'             => ['AdminController', 'commandes',           $G, ['admin']],
    'admin_finances'              => ['AdminController', 'finances',            $G, ['admin']],
    'admin_publicites'            => ['AdminController', 'publicites',          $G, ['admin']],
    'admin_publicite_save'        => ['AdminController', 'publiciteSave',       $P, ['admin']],
    'admin_publicite_supprimer'   => ['AdminController', 'publiciteSupprimer',  $P, ['admin']],
    'admin_utilisateurs'          => ['AdminController', 'utilisateurs',        $G, ['admin']],
    'admin_utilisateur_save'      => ['AdminController', 'utilisateurSave',     $P, ['admin']],
    'admin_utilisateur_supprimer' => ['AdminController', 'utilisateurSupprimer',$P, ['admin']],

    // ── RESPONSABLE PRODUITS ─────────────────────────────────────
    'produits_liste'   => ['ProduitsController', 'liste',    $G, ['responsable_produits']],
    'produit_ajouter'  => ['ProduitsController', 'ajouter',  $P, ['responsable_produits']],
    'produit_modifier' => ['ProduitsController', 'modifier', $P, ['responsable_produits']],

    // ── RESPONSABLE COMMANDES ────────────────────────────────────
    'commandes_gestion'=> ['GestionCommandesController', 'gestion', $G, ['responsable_commandes']],
    'commande_statut'  => ['GestionCommandesController', 'statut',  $P, ['responsable_commandes']],

    // ── AUTHENTIFICATION ─────────────────────────────────────────
    'login'            => ['AuthController', 'showLogin',    $G, null],
    'login_post'       => ['AuthController', 'login',        $P, null],
    'register'         => ['AuthController', 'showRegister', $G, null],
    'register_post'    => ['AuthController', 'register',     $P, null],
    'logout'           => ['AuthController', 'logout',       [$G, $P], null],   // GET = sans effet (anti « logout CSRF »)
];

/* ═════════════ DISPATCH ═════════════ */
$action = $_GET['action'] ?? 'accueil';
if (!is_string($action) || !preg_match('/^[a-z_]{1,40}$/', $action) || !isset($routes[$action])) {
    error_page(404, 'Page introuvable', "La page demandée n'existe pas.");
}
[$controller, $method, $verbs, $access] = $routes[$action];

// 1) Verbe HTTP (HEAD traité comme GET)
$verb = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($verb === 'HEAD') $verb = 'GET';
$verbs = (array)$verbs;
if (!in_array($verb, $verbs, true)) {
    header('Allow: ' . implode(', ', $verbs));
    error_page(405, 'Action non autorisée', "Cette page n'accepte pas ce type de requête.");
}

// 2) Authentification + rôle (en plus des contrôles internes des contrôleurs)
if ($access !== null) {
    $u = current_user();
    if (!$u) {
        flash_add('info', 'Veuillez vous connecter pour continuer.');
        redirect('login');
    }
    if (!in_array($u['role'], $access, true)) {
        audit('acces_refuse', "$action role={$u['role']}");
        flash_add('error', "Accès refusé : cette page n'est pas disponible avec votre compte.");
        redirect(staff_home($u['role']));
    }
}

(new $controller())->$method();
