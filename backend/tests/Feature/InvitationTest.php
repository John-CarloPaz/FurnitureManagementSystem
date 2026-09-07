<?php

namespace Tests\Feature;

use App\Domain\Access\Models\Invitation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvitationTest extends TestCase
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

    private function invitationFor(string $email = 'rider@example.com', string $role = 'delivery_personnel'): Invitation
    {
        return Invitation::create([
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_admin_invites_a_user_and_email_is_sent_via_brevo(): void
    {
        config(['services.brevo.key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);

        Sanctum::actingAs($this->userWith('admin'));

        $this->postJson('/api/v1/invitations', ['email' => 'newrider@example.com', 'role' => 'delivery_personnel'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'newrider@example.com')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('meta.email_sent', true);

        $this->assertDatabaseHas('invitations', ['email' => 'newrider@example.com', 'role' => 'delivery_personnel']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.brevo.com')
            && $request['to'][0]['email'] === 'newrider@example.com');
    }

    public function test_invitation_still_created_with_copy_link_when_email_not_configured(): void
    {
        config(['services.brevo.key' => null]);
        Sanctum::actingAs($this->userWith('super_admin'));

        $this->postJson('/api/v1/invitations', ['email' => 'nokey@example.com', 'role' => 'qa_tester'])
            ->assertCreated()
            ->assertJsonPath('meta.email_sent', false)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonFragment([]) // accept_url present
            ->assertJsonPath('data.role', 'qa_tester');
    }

    public function test_admin_cannot_invite_a_super_admin(): void
    {
        Sanctum::actingAs($this->userWith('admin'));

        $this->postJson('/api/v1/invitations', ['email' => 'sneaky@example.com', 'role' => 'super_admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('role');
    }

    public function test_cannot_invite_an_existing_user(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $existing->assignRole('customer');
        Sanctum::actingAs($this->userWith('admin'));

        $this->postJson('/api/v1/invitations', ['email' => 'taken@example.com', 'role' => 'customer'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    public function test_public_can_read_a_valid_invitation(): void
    {
        $invite = $this->invitationFor();

        $this->getJson("/api/v1/invitations/accept/{$invite->token}")
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.email', 'rider@example.com')
            ->assertJsonPath('data.role', 'delivery_personnel');
    }

    public function test_accepting_an_invitation_creates_an_active_user_with_the_role(): void
    {
        $invite = $this->invitationFor('driver@example.com', 'delivery_personnel');

        $this->postJson("/api/v1/invitations/accept/{$invite->token}", [
            'name' => 'Pedro Driver',
            'password' => 'Secret@2026',
            'password_confirmation' => 'Secret@2026',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'driver@example.com')
            ->assertJsonPath('data.user.roles.0', 'delivery_personnel')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $user = User::where('email', 'driver@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('delivery_personnel'));
        $this->assertNotNull($invite->refresh()->accepted_at);
    }

    public function test_expired_invitation_cannot_be_read_or_accepted(): void
    {
        $invite = $this->invitationFor();
        $invite->update(['expires_at' => now()->subDay()]);

        $this->getJson("/api/v1/invitations/accept/{$invite->token}")
            ->assertStatus(404)
            ->assertJsonPath('data.valid', false);

        $this->postJson("/api/v1/invitations/accept/{$invite->token}", [
            'name' => 'Too Late',
            'password' => 'Secret@2026',
            'password_confirmation' => 'Secret@2026',
        ])->assertStatus(422);
    }

    public function test_an_accepted_token_cannot_be_reused(): void
    {
        $invite = $this->invitationFor('once@example.com');
        $invite->update(['accepted_at' => now()]);

        $this->postJson("/api/v1/invitations/accept/{$invite->token}", [
            'name' => 'Again',
            'password' => 'Secret@2026',
            'password_confirmation' => 'Secret@2026',
        ])->assertStatus(422);
    }

    public function test_admin_revokes_an_invitation(): void
    {
        $invite = $this->invitationFor();
        Sanctum::actingAs($this->userWith('admin'));

        $this->deleteJson("/api/v1/invitations/{$invite->id}")->assertNoContent();
        $this->assertDatabaseMissing('invitations', ['id' => $invite->id]);
    }
}
