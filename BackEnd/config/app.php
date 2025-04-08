<?php

return [
    'name' => 'Game Store API',
    'version' => '1.0.0',
    'debug' => getenv('APP_DEBUG') ?? false,
    'timezone' => 'Asia/Ho_Chi_Minh',
    'locale' => 'vi',
    'url' => getenv('APP_URL') ?? 'http://localhost',
    'key' => getenv('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'cache' => [
        'driver' => 'redis',
        'prefix' => 'game_store_',
        'redis' => [
            'host' => getenv('REDIS_HOST') ?? '127.0.0.1',
            'password' => getenv('REDIS_PASSWORD') ?? null,
            'port' => getenv('REDIS_PORT') ?? 6379,
            'database' => getenv('REDIS_DB') ?? 0,
        ],
        'ttl' => [
            'default' => 3600,
            'game_list' => 1800,
            'user_profile' => 3600,
            'news_list' => 1800,
            'categories' => 86400,
        ]
    ],

    'logging' => [
        'default' => 'daily',
        'channels' => [
            'daily' => [
                'driver' => 'daily',
                'path' => __DIR__ . '/../logs/app.log',
                'level' => 'debug',
                'days' => 14,
            ],
            'error' => [
                'driver' => 'daily',
                'path' => __DIR__ . '/../logs/error.log',
                'level' => 'error',
                'days' => 30,
            ],
            'audit' => [
                'driver' => 'daily',
                'path' => __DIR__ . '/../logs/audit.log',
                'level' => 'info',
                'days' => 90,
            ],
            'payment' => [
                'driver' => 'daily',
                'path' => __DIR__ . '/../logs/payment.log',
                'level' => 'info',
                'days' => 90,
            ],
        ],
    ],

    'mail' => [
        'driver' => 'smtp',
        'host' => getenv('MAIL_HOST'),
        'port' => getenv('MAIL_PORT'),
        'username' => getenv('MAIL_USERNAME'),
        'password' => getenv('MAIL_PASSWORD'),
        'encryption' => getenv('MAIL_ENCRYPTION'),
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS'),
            'name' => getenv('MAIL_FROM_NAME'),
        ],
    ],

    'queue' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => null,
    ],

    'session' => [
        'driver' => 'redis',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'cookie' => 'game_store_session',
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax',
    ],

    'security' => [
        'scanning' => [
            'enabled' => true,
            'interval' => 3600, // 1 hour
            'vulnerability_scanning' => true,
            'penetration_testing' => true,
            'security_audit' => true,
            'report_path' => storage_path('logs/security'),
        ],
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
        'csrf' => [
            'enabled' => true,
            'token_name' => 'csrf_token',
        ],
        'jwt' => [
            'secret' => getenv('JWT_SECRET'),
            'ttl' => 60, // minutes
            'refresh_ttl' => 20160, // minutes (14 days)
        ],
    ],

    'upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'document' => ['pdf', 'doc', 'docx'],
        ],
        'path' => [
            'images' => 'uploads/images',
            'documents' => 'uploads/documents',
        ],
    ],

    'api' => [
        'version' => 'v1',
        'prefix' => 'api',
        'throttle' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => [],
        'max_age' => 86400,
        'supports_credentials' => true,
    ],

    'load_balancer' => [
        'enabled' => true,
        'algorithm' => 'round_robin', // round_robin, least_connections, ip_hash
        'health_check' => [
            'enabled' => true,
            'interval' => 60, // seconds
            'timeout' => 5, // seconds
            'path' => '/health',
        ],
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost'),
                'health_check_url' => env('APP_URL', 'http://localhost') . '/health',
                'weight' => 1,
            ],
        ],
    ],

    'cdn' => [
        'enabled' => true,
        'domain' => env('CDN_DOMAIN', 'https://cdn.example.com'),
        'api_key' => env('CDN_API_KEY'),
        'purge_url' => env('CDN_PURGE_URL'),
        'stats_url' => env('CDN_STATS_URL'),
        'cache_busting' => true,
        'excluded_paths' => [
            '/api/*',
            '/admin/*',
        ],
    ],

    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'collection_interval' => 60, // seconds
            'retention_period' => 86400, // 24 hours
        ],
        'alert_thresholds' => [
            'cpu_usage' => 80, // percentage
            'memory_usage' => 80, // percentage
            'disk_usage' => 80, // percentage
            'response_time' => 1000, // milliseconds
            'error_rate' => 5, // percentage
        ],
        'notifications' => [
            'enabled' => true,
            'webhook_url' => env('MONITORING_WEBHOOK_URL'),
            'channels' => [
                'email' => [
                    'enabled' => true,
                    'recipients' => explode(',', env('MONITORING_EMAIL_RECIPIENTS', '')),
                ],
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => env('MONITORING_SLACK_WEBHOOK_URL'),
                ],
            ],
        ],
        'dashboard' => [
            'enabled' => true,
            'refresh_interval' => 30, // seconds
            'history_period' => 86400, // 24 hours
        ],
    ],
]; 