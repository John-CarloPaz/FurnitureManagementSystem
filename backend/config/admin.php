<?php

/*
|--------------------------------------------------------------------------
| Default admin (seeded by RolePermissionSeeder)
|--------------------------------------------------------------------------
| Read via config() — NOT env() directly — so the values survive
| `php artisan config:cache` in production (where raw env() returns null).
*/

return [
    'name' => env('ADMIN_NAME', 'Cedarside Admin'),
    'email' => env('ADMIN_EMAIL', 'admin@cedarside.local'),
    'password' => env('ADMIN_PASSWORD'),
];
