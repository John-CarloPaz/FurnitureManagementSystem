<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function owner(): User
    {
        return User::where('email', config('owner.email'))->firstOrFail();
    }

    private function superAdmin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('super_admin');

        return $u;
    }

    public function test_the_owner_is_seeded_as_a_protected_super_admin(): void
    {
        $owner = $this->owner();
        $this->assertTrue($owner->isOwner());
        $this->assertTrue($owner->hasRole('super_admin'));
        $this->assertSame(1, User::where('is_owner', true)->count());
    }

    public function test_another_super_admin_cannot_deactivate_delete_or_demote_the_owner(): void
    {
        $owner = $this->owner();
        Sanctum::actingAs($this->superAdmin());

        $this->patchJson("/api/v1/users/{$owner->id}", ['is_active' => false])->assertStatus(422);
        $this->patchJson("/api/v1/users/{$owner->id}", ['role' => 'admin'])->assertStatus(422);
        $this->deleteJson("/api/v1/users/{$owner->id}")->assertStatus(422);

        $this->assertTrue($owner->fresh()->isOwner());
        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_only_the_owner_can_transfer_ownership(): void
    {
        $target = $this->superAdmin();

        // A non-owner super admin cannot transfer ownership.
        Sanctum::actingAs($this->superAdmin());
        $this->postJson('/api/v1/owner/transfer', ['user_id' => $target->id])->assertForbidden();
    }

    public function test_owner_transfers_ownership_and_the_flag_moves(): void
    {
        $owner = $this->owner();
        $target = User::factory()->create(['is_active' => true]);

        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/owner/transfer', ['user_id' => $target->id])
            ->assertOk()
            ->assertJsonPath('data.is_owner', true);

        $this->assertFalse($owner->fresh()->isOwner());
        $this->assertTrue($target->fresh()->isOwner());
        $this->assertTrue($target->fresh()->hasRole('super_admin')); // promoted on transfer
        $this->assertSame(1, User::where('is_owner', true)->count());
    }
}
