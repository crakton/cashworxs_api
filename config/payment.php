<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This defines the default payment gateway to use when none is specified
    |
    */
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'cashworxs'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Fallback Order
    |--------------------------------------------------------------------------
    |
    | Define the order of gateways to try when payment fails
    |
    */
    'fallback_order' => [
        'cashworxs',
        'paystack',
        'flutterwave',
        // 'stripe'
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    |
    | Configuration for each payment gateway
    |
    */
    'gateways' => [
        'cashworxs' => [
            'name' => 'Cashworxs',
            'base_url' => env('CASHWORXS_BASE_URL', 'https://server.inteliworxtest.com'),
            'access_key' => env('CASHWORXS_ACCESS_KEY'),
            'access_secret' => env('CASHWORXS_ACCESS_SECRET'),
            'enabled' => env('CASHWORXS_ENABLED', true),
            'webhook_url' => env('CASHWORXS_WEBHOOK_URL'),
            'supports' => [
                'invoices' => true,
                'payments' => true,
                'verification' => true,
                'webhooks' => true,
                'refunds' => false
            ]
        ],

        'paystack' => [
            'name' => 'Paystack',
            'base_url' => env('PAYSTACK_PAYMENT_URL','https://api.paystack.co'),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'enabled' => env('PAYSTACK_ENABLED', false),
            'webhook_url' => env('PAYSTACK_WEBHOOK_URL'),
            'supports' => [
                'invoices' => true,
                'payments' => true,
                'verification' => true,
                'webhooks' => true,
                'refunds' => true
            ]
        ],

        'flutterwave' => [
            'name' => 'Flutterwave',
            'base_url' => 'https://api.flutterwave.com/v3',
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'enabled' => env('FLUTTERWAVE_ENABLED', false),
            'webhook_url' => env('FLUTTERWAVE_WEBHOOK_URL'),
            'supports' => [
                'invoices' => true,
                'payments' => true,
                'verification' => true,
                'webhooks' => true,
                'refunds' => true
            ]
        ],

        // 'stripe' => [
        //     'name' => 'Stripe',
        //     'base_url' => 'https://api.stripe.com/v1',
        //     'public_key' => env('STRIPE_PUBLIC_KEY'),
        //     'secret_key' => env('STRIPE_SECRET_KEY'),
        //     'enabled' => env('STRIPE_ENABLED', false),
        //     'webhook_url' => env('STRIPE_WEBHOOK_URL'),
        //     'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        //     'supports' => [
        //         'invoices' => true,
        //         'payments' => true,
        //         'verification' => true,
        //         'webhooks' => true,
        //         'refunds' => true
        //     ]
        // ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | General payment settings
    |
    */
    'settings' => [
        'currency' => env('PAYMENT_CURRENCY', 'NGN'),
        'retry_attempts' => env('PAYMENT_RETRY_ATTEMPTS', 3),
        'timeout' => env('PAYMENT_TIMEOUT', 30),
        'auto_verify' => env('PAYMENT_AUTO_VERIFY', true),
        'webhook_timeout' => env('PAYMENT_WEBHOOK_TIMEOUT', 10),
        'enable_logging' => env('PAYMENT_ENABLE_LOGGING', true),
        'test_mode' => env('PAYMENT_TEST_MODE', false)
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Status Mapping
    |--------------------------------------------------------------------------
    |
    | Map gateway-specific statuses to standard statuses
    |
    */
    'status_mapping' => [
        'cashworxs' => [
            '1' => 'completed',
            '0' => 'pending',
            'failed' => 'failed'
        ],
        'paystack' => [
            'success' => 'completed',
            'pending' => 'pending',
            'failed' => 'failed',
            'abandoned' => 'failed'
        ],
        'flutterwave' => [
            'successful' => 'completed',
            'pending' => 'pending',
            'failed' => 'failed',
            'cancelled' => 'failed'
        ],
        // 'stripe' => [
        //     'succeeded' => 'completed',
        //     'pending' => 'pending',
        //     'failed' => 'failed',
        //     'canceled' => 'failed'
        // ]
    ]
];