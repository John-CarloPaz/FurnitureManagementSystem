<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWith(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_super_admin_creates_a_role_with_ticked_permissions(): void
    {
        Sanctum::actingAs($this->userWith('super_admin'));

        $this->postJson('/api/v1/roles', [
            'name' => 'Quality Checker',
            'permissions' => ['products.viewAny', 'manufacturing.view', 'manufacturing.verify'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Quality Checker')
            ->assertJsonPath('data.is_system', false)
            ->assertJsonPath('data.permissions', ['products.viewAny', 'manufacturing.view', 'manufacturing.verify']);

        $this->assertTrue(Role::findByName('Quality Checker', 'web')->hasPermissionTo('manufacturing.verify'));
    }

    public function test_admin_cannot_create_roles(): void
    {
        Sanctum::actingAs($this->userWith('admin'));

        $this->postJson('/api/v1/roles', ['name' => 'Hacker', 'permissions' => []])
            ->assertForbidden();
    }

    public function test_admin_can_read_roles_and_permissions_catalog(): void
    {
        Sanctum::actingAs($this->userWith('admin'));

        $this->getJson('/api/v1/roles')->assertOk();
        $this->getJson('/api/v1/permissions')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'products');
    }

    public function test_super_admin_updates_a_custom_role(): void
    {
        Sanctum::actingAs($this->userWith('super_admin'));
        $role = Role::create(['name' => 'Rider', 'guard_name' => 'web']);
        $role->syncPermissions(['delivery.view']);

        $this->patchJson("/api/v1/roles/{$role->id}", [
            'permissions' => ['delivery.view', 'delivery.update', 'delivery.proof.upload'],
        ])
            ->assertOk()
            ->assertJsonPath('data.permissions', ['delivery.view', 'delivery.update', 'delivery.proof.upload']);
    }

    public function test_system_roles_are_locked(): void
    {
        Sanctum::actingAs($this->userWith('super_admin'));
        $customer = Role::findByName('customer', 'web');

        $this->patchJson("/api/v1/roles/{$customer->id}", ['permissions' => []])->assertStatus(422);
        $this->deleteJson("/api/v1/roles/{$customer->id}")->assertStatus(422);
    }

    public function test_cannot_delete_a_role_that_still_has_users(): void
    {
        Sanctum::actingAs($this->userWith('super_admin'));
        $role = Role::create(['name' => 'Temp', 'guard_name' => 'web']);
        $this->userWith('Temp');

        $this->deleteJson("/api/v1/roles/{$role->id}")->assertStatus(422);

        $role->refresh(); // still there
        $this->assertDatabaseHas('roles', ['name' => 'Temp']);
    }

    public function test_invalid_permission_is_rejected(): void
    {
        Sanctum::actingAs($this->userWith('super_admin'));

        $this->postJson('/api/v1/roles', ['name' => 'Bad', 'permissions' => ['products.nuke']])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('permissions.0');
    }
}
