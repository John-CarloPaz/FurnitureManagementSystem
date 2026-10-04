<?php

namespace App\Domain\Notifications\Actions;

use App\Domain\Access\Services\BrevoMailer;
use App\Domain\Orders\Models\ReturnRequest;

/** Emails the customer whenever their return/refund request changes status (via Brevo). */
class SendReturnStatusEmail
{
    public function __construct(private readonly BrevoMailer $mailer) {}

    public function execute(ReturnRequest $return): bool
    {
        $customer = $return->requester;
        if ($customer === null) {
            return false;
        }

        $company = (string) config('services.brevo.sender_name', 'Cedarside Holding Corp.');
        $orderNumber = $return->order->order_number;
        $headline = self::headline($return->status);
        $trackUrl = config('invitations.frontend_url')."/shop/orders/{$return->order_id}";

        return $this->mailer->send(
            $customer->email,
            $customer->name,
            "Return for {$orderNumber} — {$headline}",
            $this->html($company, $headline, self::message($return, $orderNumber), $return->resolution_note, (string) $trackUrl),
        );
    }

    private static function headline(string $status): string
    {
        return match ($status) {
            'APPROVED' => 'Return approved',
            'REJECTED' => 'Return declined',
            'REFUNDED' => 'Refund processed',
            default => 'Return update',
        };
    }

    private static function message(ReturnRequest $return, string $orderNumber): string
    {
        return match ($return->status) {
            'APPROVED' => "Good news — your return request for order {$orderNumber} has been approved. We'll follow up with the next steps.",
            'REJECTED' => "Your return request for order {$orderNumber} was not approved.",
            'REFUNDED' => 'Your refund'.($return->refund_amount !== null ? ' of ₱'.number_format((float) $return->refund_amount, 2) : '')." for order {$orderNumber} has been processed.",
            default => "There's an update on your return request for order {$orderNumber}.",
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
