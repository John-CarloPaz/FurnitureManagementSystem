<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
| The SPA (Vercel) and API (Laravel Cloud) live on different origins, so the
| browser needs CORS on the API. We use bearer-token auth (no cookies), so
| credentials are not required. In production set CORS_ALLOWED_ORIGINS to your
| Vercel URL(s), comma-separated; defaults to "*" for local/dev.
*/

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(
        array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
