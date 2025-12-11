<?php

return [
    // Environnement: 'production' ou 'sandbox' (utilisé pour les tests et la fonction clearSandboxMemory)
    'env' => env('ULYS_ENV', 'sandbox'),

    // URL de base de l'API (Sandbox ou Production)
    'base_url' => env('ULYS_BASE_URL', 'https://api-partner-sandbox.ulys.com'),

    // Identifiants pour l'authentification (à configurer dans .env)
    'client_id' => env('ULYS_CLIENT_ID', ''),
    'client_secret' => env('ULYS_CLIENT_SECRET', ''),
    'username' => env('ULYS_USERNAME', ''),
    'password' => env('ULYS_PASSWORD', ''),

    // Fréquence de récupération des consommations (en jours)
    'retrieval_days' => 7,
];
