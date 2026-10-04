<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Services\BrevoMailer;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/** Emails a password-reset link via Brevo. Logs the link when email isn't configured (local dev). */
class SendPasswordResetEmail
{
    public function __construct(private readonly BrevoMailer $mailer) {}

    public function execute(User $user, string $token): bool
    {
        $company = (string) config('services.brevo.sender_name', 'Cedarside Holding Corp.');
        $url = config('invitations.frontend_url').'/reset-password?token='.$token.'&email='.urlencode($user->email);

        $sent = $this->mailer->send(
            $user->email,
            $user->name,
            "Reset your {$company} password",
            $this->html($company, (string) $url),
        );

        if (! $sent) {
            // No Brevo key (e.g. local) — surface the link in the log so it's still testable.
            Log::info('Password reset link (email not sent)', ['email' => $user->email, 'url' => $url]);
        }

        return $sent;
    }

    private function html(string $company, string $url): string
    {
        $safeUrl = e($url);

        return <<<HTML
        <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:0 auto;color:#1c1815">
          <h2 style="font-weight:600">Reset your password</h2>
          <p>We received a request to reset your {$company} password. Click below to choose a new one:</p>
          <p style="margin:28px 0">
            <a href="{$safeUrl}" style="background:#5b3a29;color:#fdf8f1;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">Reset password</a>
          </p>
          <p style="color:#6b6259;font-size:13px">Or paste this link into your browser:<br><a href="{$safeUrl}">{$safeUrl}</a></p>
          <p style="color:#6b6259;font-size:13px">This link expires shortly. If you didn't request it, you can safely ignore this email.</p>
        </div>
        HTML;
    }
}
