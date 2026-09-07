<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Models\Invitation;
use App\Domain\Access\Services\BrevoMailer;
use Illuminate\Support\Str;

/** Renders the invitation email and sends it via Brevo. Returns whether it was sent. */
class SendInvitationEmail
{
    public function __construct(private readonly BrevoMailer $mailer) {}

    public function execute(Invitation $invitation): bool
    {
        $company = config('services.brevo.sender_name', 'Cedarside Holding Corp.');
        $role = Str::of($invitation->role)->replace('_', ' ')->title();

        return $this->mailer->send(
            $invitation->email,
            null,
            "You're invited to join {$company}",
            $this->html($invitation->acceptUrl(), (string) $company, (string) $role),
        );
    }

    private function html(string $url, string $company, string $role): string
    {
        $safeUrl = e($url);

        return <<<HTML
        <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:0 auto;color:#1c1815">
          <h2 style="font-weight:600">Join {$company}</h2>
          <p>You've been invited to join the team as <strong>{$role}</strong>.</p>
          <p>Click below to set your name and password and activate your account:</p>
          <p style="margin:28px 0">
            <a href="{$safeUrl}" style="background:#5b3a29;color:#fdf8f1;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">Accept invitation</a>
          </p>
          <p style="color:#6b6259;font-size:13px">Or paste this link into your browser:<br><a href="{$safeUrl}">{$safeUrl}</a></p>
          <p style="color:#6b6259;font-size:13px">This invitation expires in a few days. If you weren't expecting it, you can ignore this email.</p>
        </div>
        HTML;
    }
}
