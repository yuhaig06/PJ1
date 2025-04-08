<?php

return [
    // Rate Limiting Configuration
    'rate_limit' => [
        'requests' => 60, // Số request tối đa
        'window' => 60, // Thời gian (giây)
    ],

    // CSRF Protection Configuration
    'csrf' => [
        'token_length' => 32,
        'token_name' => 'csrf_token',
        'header_name' => 'X-CSRF-TOKEN',
    ],

    // Input Validation Rules
    'validation_rules' => [
        'email' => 'email',
        'password' => 'string',
        'username' => 'string',
        'full_name' => 'string',
        'phone' => 'string',
        'address' => 'string',
        'price' => 'float',
        'amount' => 'float',
        'status' => 'string',
        'content' => 'string',
        'description' => 'string',
        'title' => 'string',
        'slug' => 'string',
        'thumbnail' => 'string',
        'category_id' => 'int',
        'tag_id' => 'int',
        'game_id' => 'int',
        'news_id' => 'int',
        'user_id' => 'int',
        'order_id' => 'int',
        'transaction_id' => 'string',
    ],

    // Password Policy
    'password_policy' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => true,
    ],

    // Session Configuration
    'session' => [
        'lifetime' => 120, // minutes
        'secure' => true,
        'http_only' => true,
        'same_site' => 'Lax',
    ],

    // JWT Configuration
    'jwt' => [
        'secret' => getenv('JWT_SECRET'),
        'algorithm' => 'HS256',
        'access_token_lifetime' => 60, // minutes
        'refresh_token_lifetime' => 1440, // minutes (24 hours)
    ],

    // File Upload Security
    'upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
        ],
        'max_width' => 1920,
        'max_height' => 1080,
    ],

    // CORS Configuration
    'cors' => [
        'allowed_origins' => [
            'http://localhost:3000',
            'http://localhost:8080',
        ],
        'allowed_methods' => [
            'GET',
            'POST',
            'PUT',
            'DELETE',
            'OPTIONS',
        ],
        'allowed_headers' => [
            'Content-Type',
            'Authorization',
            'X-CSRF-TOKEN',
        ],
        'max_age' => 86400, // 24 hours
    ],
]; 