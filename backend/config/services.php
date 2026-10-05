<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-3.1-flash-lite'),
    ],

    'agromonitoring' => [
        'key' => env('AGROMONITORING_API_KEY'),
    ],

    'paymongo' => [
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
    ],

    'psgc' => [
        'base_url' => env('PSGC_BASE_URL', 'https://psgc.gitlab.io/api'),
    ],

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
    ],

    'da_prices' => [
        'enabled' => env('DA_PRICE_SYNC_ENABLED', true),
        'base_url' => env('DA_PRICE_BASE_URL', 'http://www.bantaypresyo.da.gov.ph'),
        'endpoints' => [
            ['page' => 'rice', 'commodities' => [1, 2]],
            // veg 9: bulb & spice group (onion, garlic, ginger, chili).
            ['page' => 'veg', 'commodities' => [6, 7, 9]],
            ['page' => 'fruits', 'commodities' => [5]],
        ],
        'regions' => ['130000000', '040000000', '030000000'],
        'timeout' => env('DA_PRICE_TIMEOUT', 10),
        'max_attempts' => env('DA_PRICE_MAX_ATTEMPTS', 10),
        'ai_fallback' => env('DA_PRICE_AI_FALLBACK', true),
    ],

    'ai_estimates' => [
        'enabled' => env('AI_PRICE_ESTIMATES_ENABLED', true),
    ],

    'txtflow' => [
        'enabled' => env('TXTFLOW_ENABLED', false),
        'base_url' => env('TXTFLOW_BASE_URL', 'https://www.txtflow.xyz'),
        'api_key' => env('TXTFLOW_API_KEY'),
        'device_id' => env('TXTFLOW_DEVICE_ID'),
    ],

    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY', 'node'),
        'node_module_path' => env('BROWSERSHOT_NODE_MODULE_PATH', base_path('node_modules')),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        'no_sandbox' => env('BROWSERSHOT_NO_SANDBOX', false),
    ],

];
