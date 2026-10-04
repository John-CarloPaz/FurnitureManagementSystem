<?php

/*
|--------------------------------------------------------------------------
| System owner (root)
|--------------------------------------------------------------------------
| The root super-admin whose access no one can revoke. Only this account can
| transfer system ownership to another user. Seeded by RolePermissionSeeder,
| but only designated owner if the system doesn't already have one (so a
| later ownership transfer isn't clobbered by a reseed).
*/

return [
    'name' => env('OWNER_NAME', 'Cedarside Owner'),
    'email' => env('OWNER_EMAIL', 'furniturecedarside@gmail.com'),
    'password' => env('OWNER_PASSWORD', env('ADMIN_PASSWORD')),
];
