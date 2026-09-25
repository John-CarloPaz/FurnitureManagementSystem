<?php

namespace App\Domain\Access\Actions;

use App\Domain\Access\Services\BrevoMailer;
use App\Models\User;

/** Sends a friendly welcome email to a freshly-registered customer via Brevo. */
class SendWelcomeEmail
{
    public function __construct(private readonly BrevoMailer $mailer) {}

    public function execute(User $user): bool
    {
        $company = (string) config('services.brevo.sender_name', 'Cedarside Holding Corp.');
        $shopUrl = config('invitations.frontend_url').'/shop';

        return $this->mailer->send(
            $user->email,
            $user->name,
            "Welcome to {$company}!",
            $this->html($company, $user->name, (string) $shopUrl),
        );
    }

    private function html(string $company, string $name, string $shopUrl): string
    {
        $safeUrl = e($shopUrl);
        $safeName = e($name);

        return <<<HTML
        <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:0 auto;color:#1c1815">
          <h2 style="font-weight:600">Welcome, {$safeName}! 🎉</h2>
          <p>Your {$company} account is all set. Thanks for joining us.</p>
          <p>Browse our handcrafted furniture, view every piece in interactive 3D, and track your orders from our workshop to your door.</p>
          <p style="margin:28px 0">
            <a href="{$safeUrl}" style="background:#5b3a29;color:#fdf8f1;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">Start shopping</a>
          </p>
          <p style="color:#6b6259;font-size:13px">Enjoy the experience — we're glad you're here.</p>
        </div>
        HTML;
    }
}
