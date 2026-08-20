<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the 7 roles + permission matrix from docs/design/RBAC.md, plus a default
 * admin from ADMIN_* env. Order *fulfillment* transitions are role-owned (see
 * Domain\Orders\States\TransitionOwnership) and are not permissions here.
 */
class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'users.view', 'users.manage', 'roles.manage',
        'products.viewAny', 'products.browse', 'products.manage', 'products.publish',
        'orders.viewAny', 'orders.view.own', 'orders.place', 'orders.cancel',
        'manufacturing.view', 'manufacturing.stage.update', 'manufacturing.verify', 'manufacturing.schedule', 'workorders.assign',
        'delivery.view', 'delivery.assign', 'delivery.update', 'delivery.proof.upload',
        'notifications.view.own',
        'kpi.view', 'kpi.view.shopfloor', 'kpi.view.delivery',
        'audit.view', 'reports.run',
    ];

    private const ROLES = [
        'admin' => ['*'],
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

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role)
                ->syncPermissions($permissions === ['*'] ? Permission::all() : $permissions);
        }

        $this->seedAdmin();
    }

    private function seedAdmin(): void
    {
        // config() (not raw env()) so it survives config:cache in production.
        $password = config('admin.password');
        if (! $password) {
            $this->command?->warn('ADMIN_PASSWORD not set — skipping admin seed.');

            return;
        }

        // updateOrCreate so re-seeding also resets an existing admin's password.
        User::updateOrCreate(
            ['email' => config('admin.email')],
            ['name' => config('admin.name'), 'password' => $password, 'is_active' => true],
        )->syncRoles(['admin']);
    }
}
