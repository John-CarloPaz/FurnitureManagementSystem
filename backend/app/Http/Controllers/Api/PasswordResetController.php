<?php

namespace App\Http\Controllers\Api;

use App\Domain\Access\Actions\SendPasswordResetEmail;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/** Public forgot-password / reset flow. Reset emails go out via Brevo (see SendPasswordResetEmail). */
class PasswordResetController extends Controller
{
    /** Create a reset token and email the link. Always returns a generic message (no account enumeration). */
    public function forgot(Request $request, SendPasswordResetEmail $mailer): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $validated['email'])->first();
        if ($user !== null && $user->is_active) {
            $token = Password::broker()->createToken($user);
            $mailer->execute($user, $token);
        }

        return response()->json(['data' => [
            'message' => 'If that email is registered, a password reset link is on its way.',
        ]]);
    }

    /** Validate the token and set the new password. */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save(); // 'hashed' cast hashes on save
                $user->tokens()->delete(); // revoke existing API tokens
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['data' => ['message' => 'Your password has been reset. You can now sign in.']]);
        }

        throw ValidationException::withMessages(['email' => [__($status)]]);
    }
}
