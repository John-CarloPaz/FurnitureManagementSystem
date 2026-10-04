<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_emails_a_reset_link_via_brevo(): void
    {
        config(['services.brevo.key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);

        $user = User::factory()->create(['email' => 'reset@example.com', 'is_active' => true]);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reset@example.com'])
            ->assertOk()
            ->assertJsonPath('data.message', 'If that email is registered, a password reset link is on its way.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.brevo.com')
            && $request['to'][0]['email'] === $user->email
            && str_contains((string) $request['subject'], 'Reset your'));
    }

    public function test_forgot_password_is_quiet_for_unknown_emails(): void
    {
        config(['services.brevo.key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

        Http::assertNothingSent();
    }

    public function test_user_resets_their_password_with_a_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'u@example.com']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'u@example.com',
            'password' => 'BrandNew@2026',
            'password_confirmation' => 'BrandNew@2026',
        ])->assertOk()->assertJsonPath('data.message', 'Your password has been reset. You can now sign in.');

        $this->assertTrue(Hash::check('BrandNew@2026', $user->fresh()->password));
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        User::factory()->create(['email' => 'u@example.com']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'u@example.com',
            'password' => 'BrandNew@2026',
            'password_confirmation' => 'BrandNew@2026',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
