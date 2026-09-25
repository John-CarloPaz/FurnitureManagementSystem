<?php

namespace App\Domain\Notifications\Actions;

use App\Domain\Access\Services\BrevoMailer;
use App\Domain\Notifications\Notifications\OrderStatusNotification;
use App\Domain\Orders\Models\Order;

/**
 * Emails the customer the key milestones of their order via Brevo (the transport the
 * invitation flow already uses). The in-app bell still fires for every status change;
 * this only sends real email for the moments that matter to the buyer.
 */
class SendOrderEmail
{
    /** Order statuses that warrant a customer email. */
    public const MILESTONES = ['PLACED', 'IN_PRODUCTION', 'OUT_FOR_DELIVERY', 'DELIVERED'];

    public function __construct(private readonly BrevoMailer $mailer) {}

    public static function isMilestone(string $status): bool
    {
        return in_array($status, self::MILESTONES, true);
    }

    public function execute(Order $order, string $status): bool
    {
        $customer = $order->customer;
        if ($customer === null || ! self::isMilestone($status)) {
            return false;
        }

        $company = (string) config('services.brevo.sender_name', 'Cedarside Holding Corp.');
        $headline = self::headline($status);
        $message = OrderStatusNotification::message($status, $order->order_number);
        $trackUrl = config('invitations.frontend_url')."/shop/orders/{$order->id}";

        return $this->mailer->send(
            $customer->email,
            $customer->name,
            "Order {$order->order_number} — {$headline}",
            $this->html($company, $headline, $message, (string) $trackUrl),
        );
    }

    private static function headline(string $status): string
    {
        return match ($status) {
            'PLACED' => 'Order received',
            'IN_PRODUCTION' => 'Now in production',
            'OUT_FOR_DELIVERY' => 'Out for delivery',
            'DELIVERED' => 'Delivered',
            default => 'Order update',
        };
    }

    private function html(string $company, string $headline, string $message, string $trackUrl): string
    {
        $safeUrl = e($trackUrl);
        $safeHeadline = e($headline);
        $safeMessage = e($message);

        return <<<HTML
        <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:0 auto;color:#1c1815">
          <h2 style="font-weight:600">{$safeHeadline}</h2>
          <p>{$safeMessage}</p>
          <p style="margin:28px 0">
            <a href="{$safeUrl}" style="background:#5b3a29;color:#fdf8f1;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">Track your order</a>
          </p>
          <p style="color:#6b6259;font-size:13px">Thank you for choosing {$company}.</p>
        </div>
        HTML;
    }
}
