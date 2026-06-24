<?php

return [

    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 1. REMOVE the '*' wildcard. 
    // 2. Add your EXACT Vercel URL (no trailing slash) and your local dev URL.
    'allowed_origins' => [
        'https://tssdappbackend.vercel.app', 
        'http://localhost:5173', 
        'http://localhost:3000'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];