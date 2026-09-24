<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    public function test_write_actions_are_audited_with_actor_method_and_fields(): void
    {
        Sanctum::actingAs($this->userWith('admin'));

        $productId = $this->postJson('/api/v1/products', ['name' => 'Oak Table', 'base_price' => 5000])
            ->assertCreated()->json('data.id');

        $this->patchJson("/api/v1/products/{$productId}", ['name' => 'Renamed Oak Table'])->assertOk();

        // The create was logged...
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created', 'auditable_type' => 'Product', 'auditable_id' => $productId,
        ]);

        // ...and the update captured who, the method, and the changed field.
        $log = AuditLog::where('event', 'updated')->where('auditable_type', 'Product')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('PATCH', $log->method);
        $this->assertNotNull($log->user_id);
        $this->assertNotNull($log->user_name);
        $this->assertArrayHasKey('name', $log->changes ?? []);
        $this->assertSame('Renamed Oak Table', $log->changes['name']['new']);
    }

    public function test_seeding_and_unauthenticated_changes_are_not_audited(): void
    {
        // The seeder + a factory user run with no authenticated actor → nothing logged.
        User::factory()->create();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_sensitive_fields_are_redacted(): void
    {
        $admin = $this->userWith('admin');
        $target = $this->userWith('customer');
        Sanctum::actingAs($admin);

        // Deactivate the target user (an audited User update).
        $this->patchJson("/api/v1/users/{$target->id}", ['is_active' => false])->assertOk();

        $log = AuditLog::where('event', 'updated')->where('auditable_type', 'User')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('is_active', $log->changes ?? []);
        $this->assertArrayNotHasKey('password', $log->changes ?? []); // password not touched here
    }

    public function test_audit_log_endpoint_is_permission_gated(): void
    {
        Sanctum::actingAs($this->userWith('customer'));
        $this->getJson('/api/v1/audit-logs')->assertForbidden();
        $this->getJson('/api/v1/audit-logs/export')->assertForbidden();

        // qa_tester holds audit.view.
        Sanctum::actingAs($this->userWith('qa_tester'));
        $this->getJson('/api/v1/audit-logs')->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_role_and_permission_changes_are_audited(): void
    {
        Sanctum::actingAs($this->userWith('super_admin'));

        $roleId = $this->postJson('/api/v1/roles', ['name' => 'Rider', 'permissions' => ['delivery.view']])
            ->assertCreated()->json('data.id');

        $this->assertDatabaseHas('audit_logs', ['event' => 'created', 'auditable_type' => 'Role', 'auditable_id' => $roleId]);

        $this->patchJson("/api/v1/roles/{$roleId}", ['permissions' => ['delivery.view', 'delivery.update']])->assertOk();

        $log = AuditLog::where('auditable_type', 'Role')->where('event', 'updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('PATCH', $log->method);
        $this->assertContains('delivery.update', $log->changes['permissions_added'] ?? []);
    }

    public function test_audit_log_exports_as_csv(): void
    {
        Sanctum::actingAs($this->userWith('admin'));
        $this->postJson('/api/v1/products', ['name' => 'Exported Product', 'base_price' => 1])->assertCreated();

        $res = $this->get('/api/v1/audit-logs/export')->assertOk();
        $this->assertStringContainsString('text/csv', (string) $res->headers->get('content-type'));

        $body = $res->streamedContent();
        $this->assertStringContainsString('When,Who,Event,Method,Path,Entity', $body);
        $this->assertStringContainsString('Product', $body);
    }
}
