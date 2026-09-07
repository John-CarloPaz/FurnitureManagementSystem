<?php

namespace App\Domain\Access;

/**
 * Single source of truth for the permission set and how it's grouped for the
 * super-admin role builder. The seeder creates every permission listed here, and
 * GET /permissions serves the same structure so the UI matrix and the backend can
 * never drift apart.
 *
 * `type: crud` abilities render as a Create/Read/Update/Delete row (the "core model"
 * matrix); `type: action` abilities render as toggle chips (workflow areas that don't
 * map cleanly to CRUD). `action` on a crud ability names its column.
 */
class PermissionCatalog
{
    public const SUPER_ADMIN = 'super_admin';

    public const ADMIN = 'admin';

    /**
     * Seeded roles the builder may not rename, delete, or re-scope — they wire the
     * app's default authorization and are relied on by seeders/tests.
     *
     * @var list<string>
     */
    public const SYSTEM_ROLES = [
        'super_admin', 'admin', 'production_manager', 'manufacturing_operative',
        'logistics_coordinator', 'delivery_personnel', 'customer', 'qa_tester',
    ];

    /** Role-mutating permissions — reserved for super_admin (admin gets everything else). */
    public const SUPER_ADMIN_ONLY = ['roles.create', 'roles.update', 'roles.delete'];

    /**
     * @var list<array{key: string, label: string, abilities: list<array{name: string, label: string, type: string, action?: string}>}>
     */
    public const GROUPS = [
        [
            'key' => 'products',
            'label' => 'Products & Catalogue',
            'abilities' => [
                ['name' => 'products.create', 'label' => 'Create', 'type' => 'crud', 'action' => 'create'],
                ['name' => 'products.viewAny', 'label' => 'Read', 'type' => 'crud', 'action' => 'read'],
                ['name' => 'products.update', 'label' => 'Update', 'type' => 'crud', 'action' => 'update'],
                ['name' => 'products.delete', 'label' => 'Delete', 'type' => 'crud', 'action' => 'delete'],
                ['name' => 'products.publish', 'label' => 'Publish / unpublish', 'type' => 'action'],
                ['name' => 'products.browse', 'label' => 'Browse published catalogue', 'type' => 'action'],
            ],
        ],
        [
            'key' => 'orders',
            'label' => 'Orders',
            'abilities' => [
                ['name' => 'orders.place', 'label' => 'Create', 'type' => 'crud', 'action' => 'create'],
                ['name' => 'orders.viewAny', 'label' => 'Read (all)', 'type' => 'crud', 'action' => 'read'],
                ['name' => 'orders.view.own', 'label' => 'View own orders', 'type' => 'action'],
                ['name' => 'orders.cancel', 'label' => 'Cancel', 'type' => 'action'],
            ],
        ],
        [
            'key' => 'users',
            'label' => 'Users',
            'abilities' => [
                ['name' => 'users.create', 'label' => 'Create', 'type' => 'crud', 'action' => 'create'],
                ['name' => 'users.view', 'label' => 'Read', 'type' => 'crud', 'action' => 'read'],
                ['name' => 'users.update', 'label' => 'Update', 'type' => 'crud', 'action' => 'update'],
                ['name' => 'users.delete', 'label' => 'Delete', 'type' => 'crud', 'action' => 'delete'],
            ],
        ],
        [
            'key' => 'roles',
            'label' => 'Roles & Permissions',
            'abilities' => [
                ['name' => 'roles.create', 'label' => 'Create', 'type' => 'crud', 'action' => 'create'],
                ['name' => 'roles.viewAny', 'label' => 'Read', 'type' => 'crud', 'action' => 'read'],
                ['name' => 'roles.update', 'label' => 'Update', 'type' => 'crud', 'action' => 'update'],
                ['name' => 'roles.delete', 'label' => 'Delete', 'type' => 'crud', 'action' => 'delete'],
            ],
        ],
        [
            'key' => 'invitations',
            'label' => 'Invitations',
            'abilities' => [
                ['name' => 'invitations.create', 'label' => 'Invite users', 'type' => 'crud', 'action' => 'create'],
                ['name' => 'invitations.viewAny', 'label' => 'Read', 'type' => 'crud', 'action' => 'read'],
                ['name' => 'invitations.revoke', 'label' => 'Revoke', 'type' => 'action'],
            ],
        ],
        [
            'key' => 'manufacturing',
            'label' => 'Manufacturing',
            'abilities' => [
                ['name' => 'manufacturing.view', 'label' => 'View', 'type' => 'action'],
                ['name' => 'manufacturing.stage.update', 'label' => 'Update stage', 'type' => 'action'],
                ['name' => 'manufacturing.verify', 'label' => 'Verify (QC)', 'type' => 'action'],
                ['name' => 'manufacturing.schedule', 'label' => 'Schedule production', 'type' => 'action'],
                ['name' => 'workorders.assign', 'label' => 'Assign work orders', 'type' => 'action'],
            ],
        ],
        [
            'key' => 'delivery',
            'label' => 'Delivery',
            'abilities' => [
                ['name' => 'delivery.view', 'label' => 'View', 'type' => 'action'],
                ['name' => 'delivery.assign', 'label' => 'Assign driver', 'type' => 'action'],
                ['name' => 'delivery.update', 'label' => 'Drive (dispatch / location)', 'type' => 'action'],
                ['name' => 'delivery.proof.upload', 'label' => 'Upload proof of delivery', 'type' => 'action'],
            ],
        ],
        [
            'key' => 'analytics',
            'label' => 'Analytics & Reports',
            'abilities' => [
                ['name' => 'kpi.view', 'label' => 'View KPIs', 'type' => 'action'],
                ['name' => 'kpi.view.shopfloor', 'label' => 'View shop-floor KPIs', 'type' => 'action'],
                ['name' => 'kpi.view.delivery', 'label' => 'View delivery KPIs', 'type' => 'action'],
                ['name' => 'audit.view', 'label' => 'View audit log', 'type' => 'action'],
                ['name' => 'reports.run', 'label' => 'Run reports', 'type' => 'action'],
            ],
        ],
        [
            'key' => 'notifications',
            'label' => 'Notifications',
            'abilities' => [
                ['name' => 'notifications.view.own', 'label' => 'View own notifications', 'type' => 'action'],
            ],
        ],
    ];

    /** @return list<string> every permission name in the catalog */
    public static function permissionNames(): array
    {
        $names = [];
        foreach (self::GROUPS as $group) {
            foreach ($group['abilities'] as $ability) {
                $names[] = $ability['name'];
            }
        }

        return $names;
    }

    /** Whether a role name is a locked, seeded system role. */
    public static function isSystemRole(string $name): bool
    {
        return in_array($name, self::SYSTEM_ROLES, true);
    }
}
