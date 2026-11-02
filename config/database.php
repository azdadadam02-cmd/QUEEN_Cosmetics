<?php
// Remplace par TA connexion existante : ce fichier doit RETOURNER un objet PDO.
// (Si tu as déjà config/database.php, garde le tien : il suffit qu'il définisse $pdo ou retourne le PDO.)
return new PDO(
    'mysql:host=localhost;dbname=queen_db;charset=utf8mb4',
    'root', '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
