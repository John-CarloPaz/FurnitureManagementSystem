<?php

namespace App\Domain\Access\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends transactional email through Brevo's HTTP API. Kept behind this one class so
 * the invitation flow doesn't care about the transport — if BREVO_API_KEY is unset it
 * logs and returns false, and the caller falls back to the copyable accept link.
 */
class BrevoMailer
{
    public function isConfigured(): bool
    {
        return (bool) config('services.brevo.key');
    }

    public function send(string $toEmail, ?string $toName, string $subject, string $htmlContent): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('Brevo API key not set — invitation email not sent.', ['to' => $toEmail]);

            return false;
        }

        $recipient = ['email' => $toEmail];
        if ($toName) {
            $recipient['name'] = $toName;
        }

        $response = Http::withHeaders([
            'api-key' => (string) config('services.brevo.key'),
            'accept' => 'application/json',
        ])->post((string) config('services.brevo.endpoint'), [
            'sender' => [
                'email' => config('services.brevo.sender_email'),
                'name' => config('services.brevo.sender_name'),
            ],
            'to' => [$recipient],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ]);

        if ($response->failed()) {
            Log::error('Brevo send failed.', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        }

        return true;
    }
}
