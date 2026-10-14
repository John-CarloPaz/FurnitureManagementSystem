<?php

namespace App\Domain\Notifications\Actions;

use App\Domain\Access\Services\BrevoMailer;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\Voucher;

/** Emails the customer that their order is running late, with an optional goodwill voucher. */
class SendOrderDelayedEmail
{
    public function __construct(private readonly BrevoMailer $mailer) {}

    public function execute(Order $order, ?Voucher $voucher = null): bool
    {
        $customer = $order->customer;
        if ($customer === null) {
            return false;
        }

        $company = (string) config('services.brevo.sender_name', 'Cedarside Holding Corp.');
        $trackUrl = config('invitations.frontend_url')."/shop/orders/{$order->id}";

        return $this->mailer->send(
            $customer->email,
            $customer->name,
            "A quick update on order {$order->order_number}",
            $this->html($company, $order, $voucher, (string) $trackUrl),
        );
    }

    private function html(string $company, Order $order, ?Voucher $voucher, string $trackUrl): string
    {
        $safeUrl = e($trackUrl);
        $expected = $order->expected_delivery_at?->format('M j, Y') ?? 'the estimated date';

        $voucherBlock = '';
        if ($voucher !== null) {
            $code = e($voucher->code);
            $pct = (int) (float) $voucher->value;
            $voucherBlock = <<<HTML
              <p style="margin:18px 0;padding:14px;border:1px dashed #c9a063;border-radius:8px;background:#faf5ec">
                As our apology, here's <strong>{$pct}% off</strong> your next order — use code
                <strong style="font-family:monospace">{$code}</strong> at checkout.
              </p>
            HTML;
        }

        return <<<HTML
        <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:0 auto;color:#1c1815">
          <h2 style="font-weight:600">Your order is taking a little longer</h2>
          <p>We're sorry — order <strong>{$order->order_number}</strong> has passed its estimated delivery date ({$expected}) and is still on its way. Our team is prioritising it.</p>
          {$voucherBlock}
          <p style="margin:24px 0">
            <a href="{$safeUrl}" style="background:#5b3a29;color:#fdf8f1;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">Track your order</a>
          </p>
          <p style="color:#6b6259;font-size:13px">Thank you for your patience. — {$company}</p>
        </div>
        HTML;
    }
}
