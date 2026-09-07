<?php

/*
|--------------------------------------------------------------------------
| Invitations
|--------------------------------------------------------------------------
| The accept link points at the React SPA, not the API — FRONTEND_URL must be
| the deployed frontend origin (e.g. https://cedarside.vercel.app) in production.
*/

return [
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),
    'accept_path' => env('INVITATION_ACCEPT_PATH', '/invite/accept'),
    'expires_days' => (int) env('INVITATION_EXPIRES_DAYS', 7),
];
