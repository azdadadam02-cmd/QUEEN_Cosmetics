QUEEN Cosmetics — Espace client
================================
Copie le contenu dans ton projet (fusionne app/ et public/).

1) config/database.php  -> doit retourner un PDO (ou définir $pdo). Garde le tien.
2) Session après login (AuthController) : $_SESSION['user_id'], ['user_nom'], ['user_email']
   (ou $_SESSION['user'] = ['id'=>..,'nom'=>..,'email'=>..]).
3) public/index.php : routeur en ?action= (comme ton AuthController). Si tu gardes TON index.php, ajoute seulement les routes
   accueil, panier, panier_ajouter, panier_modifier, notifications, confirmation, commande_valider (+ autoload + app/helpers.php).
4) Images : public/images/ (logo.png = logo navbar ; produits.image et publicites.image = nom de fichier).
5) Pages : accueil, panier, confirmation (paiement à la livraison), notifications.
   Statuts lus depuis commandes.statut : En attente / Confirmé / Annulé (Acceptée/Refusée/Livrée acceptés aussi).
6) Stock : décrémenté à la validation (UPDATE ... WHERE quantite_stock >= ?), refusé si rupture.

ESPACES PERSONNEL (routes ?action=)
-----------------------------------
admin                  -> admin_dashboard, admin_produits (lecture), admin_commandes (lecture), admin_utilisateurs
                          (créer / modifier / supprimer tous les comptes SAUF role=admin ; suppression refusée si l'utilisateur a des commandes)
responsable_produits   -> produits_liste (ajout de produits + upload image dans public/images/)
responsable_commandes  -> commandes_gestion (Accepter / Refuser / Annuler / Marquer livrée)
                          Refus ou annulation = stock remis en rayon ; Livrée = paiement passé à « Paye ».
Statuts DB : En attente / Acceptee / Refusee / Livree.
Routes à ajouter dans TON index.php si tu le gardes : voir public/index.php (blocs ADMIN, RESPONSABLE PRODUITS, RESPONSABLE COMMANDES).
Fichiers ajoutés : app/controllers/{Admin,Produits,GestionCommandes}Controller.php, app/models/User.php (étendu),
app/views/{admin,produits,commandes,partials,layouts/staff_*}, public/css/staff.css, public/js/staff.js.

LIVRAISON & FINANCES
--------------------
- Confirmation de commande : le client saisit nom, téléphone (format marocain), ville, adresse. Stocké dans commandes
  (colonnes nom_livraison, telephone, ville, adresse — créées automatiquement au 1er usage, ou lance database/livraison.sql).
- Responsable commandes + admin voient téléphone/adresse dans les détails de chaque commande.
- Admin > Finances : total encaissé, ce mois, à encaisser, en attente, refusé, graphique 14 jours, liste des paiements.
  Un paiement devient « Paye » quand le responsable marque la commande « Livrée ».

PUBLICITÉS (admin)
------------------
Admin > Publicités : ajouter / modifier / supprimer les offres du bandeau de l'accueil client
(titre, réduction 0-90 % — 0 = pas de badge, image de fond uploadée dans public/images/).
Route : admin_publicites (+ admin_publicite_save, admin_publicite_supprimer).


══════════════ SÉCURITÉ (ajoutée) ══════════════
Fichiers : app/security.php (nouveau), app/helpers.php, public/index.php, AuthController, .htaccess (racine, app, config, database, storage, public, public/images), config/app.php.

URL / routeur
 - Liste blanche : toute action inconnue = 404 ; action avec caractères bizarres/tableau = 404.
 - Chaque route a son verbe HTTP (GET/POST) → sinon 405. Plus de ?page=.
 - Chaque route a ses rôles → un client ne peut pas ouvrir admin_*, etc. (+ journal « acces_refuse »).
 - Dossiers app/, config/, database/, storage/ bloqués par .htaccess (accès direct = 403).
Sessions / connexion
 - Cookie HttpOnly + SameSite + nom personnalisé, mode strict, ID régénéré à la connexion (anti fixation).
 - Expiration : 30 min d'inactivité, 8 h max, déconnexion si le navigateur change (vol de cookie).
 - Le rôle est relu en base à CHAQUE requête (rôle changé/compte supprimé = effet immédiat).
 - Anti brute-force : 5 échecs/15 min par email+IP, 30 par IP ; 10 inscriptions/h par IP (table tentatives_connexion, créée toute seule).
 - Mot de passe : password_hash, comparaison à temps constant, message d'erreur identique (email inconnu ou mauvais mdp).
 - Déconnexion = POST + jeton CSRF (un lien piégé ne peut plus te déconnecter).
Formulaires
 - Jeton CSRF sur TOUS les POST + vérification de l'en-tête Origin.
 - Requêtes SQL préparées partout ; sortie HTML échappée avec e().
Navigateur
 - CSP avec nonce (bloque le JS injecté), X-Frame-Options DENY (anti clickjacking), nosniff, Referrer-Policy, Permissions-Policy, pas de cache des pages privées.
Fichiers envoyés
 - Vrai contrôle d'image (finfo + getimagesize), taille max, nom aléatoire, jamais d'exécution de PHP dans public/images.
Limites
 - Panier : 30 produits max, 20 par produit ; 5 commandes « En attente » max par client ; 10 commandes/h.
Erreurs / journal
 - Pas d'erreurs PHP affichées (pages d'erreur génériques). Journal : storage/logs/security.log.
Production : mets 'force_https' => true dans config/app.php (HSTS + cookie Secure) et change le mot de passe root MySQL.
Si tu gardes tes propres login.php / register.php : ajoute <?= csrf_field() ?> juste après la balise <form>.


══════════════ LANGUE ARABE (RTL) ══════════════
 - Bouton « العربية / Français » dans la barre de navigation (et sur les pages login / inscription).
 - Le choix est mémorisé (cookie 1 an). En arabe : textes traduits + page de droite à gauche (public/css/rtl.css).
 - Les vues restent en français ; la traduction est dans app/lang/ar.php (français => arabe). Pour traduire un nouveau texte, ajoute une ligne dans 'exact'.
 - Les données saisies (noms de produits, titres de publicités, adresses) ne sont pas traduites.
 - Si tu gardes tes propres login.php / register.php : ajoute le lien de langue (voir app/views/auth/) et vérifie que app/helpers.php est chargé en premier.
