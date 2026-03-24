<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:5174'),
        env('LANDING_URL', 'http://localhost:5173'),
        env('LANDING_URL', 'http://localhost:5174'),
        env('CORS_EXTRA_ORIGIN'),
    ])),

    // Match browser Origin even if FRONTEND_URL/LANDING_URL in .env are wrong or config is cached
    'allowed_origins_patterns' => [
        '#^https://([a-zA-Z0-9-]+\.)*clyx\.agency$#',
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
