<?php

return [
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    
    // Change this to allow all origins temporarily (or put your Vercel URL here)
    'allowed_origins' => ['*'], 
    
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    
    // Set this to false since you use Bearer tokens. If true, allowed_origins cannot be '*'
    'supports_credentials' => false, 
];