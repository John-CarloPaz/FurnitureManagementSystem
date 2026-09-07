<?php

namespace Database\Seeders;

use App\Domain\Access\PermissionCatalog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the system roles + permission matrix from PermissionCatalog (mirrored in
 * docs/design/RBAC.md), plus a default super_admin from ADMIN_* env. Order
 * *fulfillment* transitions are role-owned (see Domain\Orders\States\TransitionOwnership)
 * and are not permissions here.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Explicit permissions per system role. `super_admin` gets everything; `admin`
     * gets everything except role mutation (SUPER_ADMIN_ONLY) — see run().
     *
     * @var array<string, array<int, string>>
     */
    private const ROLES = [
        'production_manager' => [
            'products.viewAny', 'orders.viewAny',
            'manufacturing.view', 'manufacturing.stage.update', 'manufacturing.verify', 'manufacturing.schedule', 'workorders.assign',
            'kpi.view.shopfloor', 'notifications.view.own',
        ],
        'manufacturing_operative' => [
            'products.viewAny', 'orders.viewAny',
            'manufacturing.view', 'manufacturing.stage.update', 'notifications.view.own',
        ],
        'logistics_coordinator' => [
            'orders.viewAny', 'delivery.view', 'delivery.assign', 'kpi.view.delivery', 'notifications.view.own',
        ],
        'delivery_personnel' => [
            'orders.viewAny', 'delivery.view', 'delivery.update', 'delivery.proof.upload', 'notifications.view.own',
        ],
        'customer' => [
            'products.browse', 'orders.place', 'orders.view.own', 'orders.cancel', 'notifications.view.own',
        ],
        'qa_tester' => [
            'products.viewAny', 'orders.viewAny', 'manufacturing.view', 'manufacturing.verify', 'delivery.view',
            'kpi.view', 'audit.view', 'reports.run', 'notifications.view.own',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::permissionNames() as $permission) {
            Permission::findOrCreate($permission);
        }

        // super_admin: every permission. admin: everything a day-to-day owner needs,
        // minus role mutation (only super_admin may build/edit roles).
        Role::findOrCreate(PermissionCatalog::SUPER_ADMIN)->syncPermissions(Permission::all());
        Role::findOrCreate(PermissionCatalog::ADMIN)->syncPermissions(
            Permission::whereNotIn('name', PermissionCatalog::SUPER_ADMIN_ONLY)->get()
        );

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role)->syncPermissions($permissions);
        }

        $this->seedAdmin();
    }

    private function seedAdmin(): void
    {
        // config() (not raw env()) so it survives config:cache in production.
        $password = config('admin.password');
        if (! $password) {
            $this->command?->warn('ADMIN_PASSWORD not set — skipping super_admin seed.');

            return;
        }

        // updateOrCreate so re-seeding also resets an existing admin's password.
        User::updateOrCreate(
            ['email' => config('admin.email')],
            ['name' => config('admin.name'), 'password' => $password, 'is_active' => true],
        )->syncRoles([PermissionCatalog::SUPER_ADMIN]);
    }
}
