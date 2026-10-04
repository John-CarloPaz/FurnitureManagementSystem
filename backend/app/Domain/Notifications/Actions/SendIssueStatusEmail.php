<?php

namespace App\Domain\Notifications\Actions;

use App\Domain\Access\Services\BrevoMailer;
use App\Domain\Orders\Models\IssueReport;

/** Emails the customer whenever their reported issue changes status (via Brevo). */
class SendIssueStatusEmail
{
    public function __construct(private readonly BrevoMailer $mailer) {}

    public function execute(IssueReport $issue): bool
    {
        $customer = $issue->reporter;
        if ($customer === null) {
            return false;
        }

        $company = (string) config('services.brevo.sender_name', 'Cedarside Holding Corp.');
        $orderNumber = $issue->order->order_number;
        $headline = self::headline($issue->status);
        $trackUrl = config('invitations.frontend_url')."/shop/orders/{$issue->order_id}";

        return $this->mailer->send(
            $customer->email,
            $customer->name,
            "Your reported issue for {$orderNumber} — {$headline}",
            $this->html($company, $headline, self::message($issue->status, $orderNumber), $issue->resolution_note, (string) $trackUrl),
        );
    }

    private static function headline(string $status): string
    {
        return match ($status) {
            'IN_REVIEW' => 'Under review',
            'RESOLVED' => 'Issue resolved',
            default => 'Issue update',
        };
    }

    private static function message(string $status, string $orderNumber): string
    {
        return match ($status) {
            'IN_REVIEW' => "We're now looking into the issue you reported for order {$orderNumber}.",
            'RESOLVED' => "The issue you reported for order {$orderNumber} has been resolved.",
            default => "There's an update on the issue you reported for order {$orderNumber}.",
        };
    }

    private function html(string $company, string $headline, string $message, ?string $note, string $trackUrl): string
    {
        $safeUrl = e($trackUrl);
        $safeHeadline = e($headline);
        $safeMessage = e($message);
        $noteHtml = $note ? '<p style="color:#6b6259">'.e($note).'</p>' : '';

        return <<<HTML
        <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:0 auto;color:#1c1815">
          <h2 style="font-weight:600">{$safeHeadline}</h2>
          <p>{$safeMessage}</p>
          {$noteHtml}
          <p style="margin:28px 0">
            <a href="{$safeUrl}" style="background:#5b3a29;color:#fdf8f1;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">View your order</a>
          </p>
          <p style="color:#6b6259;font-size:13px">Thank you for choosing {$company}.</p>
        </div>
        HTML;
    }
}
