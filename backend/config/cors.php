<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Mirrors the framework defaults, except `exposed_headers`: the Vue SPA
    | reads the throttle `Retry-After` header to tell farmers how long to
    | wait, and browsers hide unexposed headers from cross-origin responses.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Retry-After'],

    'max_age' => 0,

    'supports_credentials' => false,

];
