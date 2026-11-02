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
