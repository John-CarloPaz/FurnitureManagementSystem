<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_updates_own_name_and_username(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'username' => 'oldhandle']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/auth/profile', ['name' => 'New Name', 'username' => 'newhandle'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.username', 'newhandle');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'username' => 'newhandle']);
    }

    public function test_username_must_be_unique_but_own_is_allowed(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create(['username' => 'mine']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/auth/profile', ['username' => 'taken'])
            ->assertStatus(422)->assertJsonValidationErrorFor('username');

        // Re-saving your own username is fine (ignores self).
        $this->patchJson('/api/v1/auth/profile', ['username' => 'mine'])->assertOk();
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(); // factory password is "password"
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/auth/profile', [
            'password' => 'NewPass@2026', 'password_confirmation' => 'NewPass@2026', 'current_password' => 'wrong',
        ])->assertStatus(422)->assertJsonValidationErrorFor('current_password');

        $this->patchJson('/api/v1/auth/profile', [
            'password' => 'NewPass@2026', 'password_confirmation' => 'NewPass@2026', 'current_password' => 'password',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPass@2026', (string) $user->refresh()->password));
    }
}
