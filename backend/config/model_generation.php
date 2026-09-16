<?php

/*
|--------------------------------------------------------------------------
| Image → 3D model generation
|--------------------------------------------------------------------------
| A photo uploaded on a listing is sent to an external image-to-3D service,
| which returns a GLB the app stores as the product's 3D model version.
| Provider is swappable (DIP) — set MODEL_GENERATION_PROVIDER + that provider's
| block. With no API key the feature is simply disabled and manual .glb upload
| remains the path.
*/

return [
    'provider' => env('MODEL_GENERATION_PROVIDER', 'meshy'),

    // How long a queued generation keeps polling before it's marked failed, and
    // how often it re-checks the provider.
    'timeout_minutes' => (int) env('MODEL_GENERATION_TIMEOUT_MINUTES', 15),
    'poll_seconds' => (int) env('MODEL_GENERATION_POLL_SECONDS', 15),

    'meshy' => [
        'key' => env('MESHY_API_KEY'),
        'endpoint' => env('MESHY_ENDPOINT', 'https://api.meshy.ai/openapi/v1'),
        'ai_model' => env('MESHY_AI_MODEL', 'latest'),
        'topology' => env('MESHY_TOPOLOGY', 'triangle'),
        // Remesh to a web-friendly polycount — without this the raw mesh is huge (tens
        // of MB) and stalls the browser viewer. Keep textures at the 2k minimum.
        'should_remesh' => (bool) env('MESHY_SHOULD_REMESH', true),
        'target_polycount' => (int) env('MESHY_TARGET_POLYCOUNT', 18000),
        'should_texture' => (bool) env('MESHY_SHOULD_TEXTURE', true),
        'texture_resolution' => env('MESHY_TEXTURE_RESOLUTION', '2k'),
    ],
];
