<?php
// Réglages de sécurité (optionnel). Tout est déjà sûr par défaut.
return [
    'debug'        => false,   // true seulement en développement (affiche les erreurs)
    'force_https'  => false,   // true quand le site est en HTTPS (active aussi HSTS + cookies Secure)
    'idle_timeout' => 1800,    // déconnexion après 30 min d'inactivité
    'max_lifetime' => 28800,   // session max 8 h
];
