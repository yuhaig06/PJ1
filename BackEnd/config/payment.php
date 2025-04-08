<?php

return [
    // Stripe Configuration
    'stripe' => [
        'secret_key' => getenv('STRIPE_SECRET_KEY'),
        'public_key' => getenv('STRIPE_PUBLIC_KEY'),
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET'),
    ],

    // PayPal Configuration
    'paypal' => [
        'client_id' => getenv('PAYPAL_CLIENT_ID'),
        'client_secret' => getenv('PAYPAL_CLIENT_SECRET'),
        'mode' => getenv('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'return_url' => getenv('APP_URL') . '/payment/paypal/return',
        'cancel_url' => getenv('APP_URL') . '/payment/paypal/cancel',
    ],

    // MoMo Configuration
    'momo' => [
        'endpoint' => getenv('MOMO_ENDPOINT'),
        'partner_code' => getenv('MOMO_PARTNER_CODE'),
        'access_key' => getenv('MOMO_ACCESS_KEY'),
        'secret_key' => getenv('MOMO_SECRET_KEY'),
        'return_url' => getenv('APP_URL') . '/payment/momo/return',
        'notify_url' => getenv('APP_URL') . '/payment/momo/notify',
    ],

    // VNPay Configuration
    'vnpay' => [
        'tmn_code' => getenv('VNPAY_TMN_CODE'),
        'hash_secret' => getenv('VNPAY_HASH_SECRET'),
        'url' => getenv('VNPAY_URL'),
        'return_url' => getenv('APP_URL') . '/payment/vnpay/return',
    ],

    // General Payment Settings
    'settings' => [
        'default_currency' => 'USD',
        'supported_currencies' => ['USD', 'VND', 'EUR', 'GBP'],
        'min_amount' => 1,
        'max_amount' => 10000,
        'payment_timeout' => 3600, // 1 hour in seconds
    ],
]; 