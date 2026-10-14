<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Actions\SendOrderDelayedEmail;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Flags orders that have blown past their expected delivery date (set from lead time at
 * placement) and aren't delivered yet, emails the customer once, and — as the consequence
 * — issues a goodwill discount voucher. Scheduled hourly in routes/console.php.
 */
class FlagDelayedOrders extends Command
{
    protected $signature = 'orders:flag-delays';

    protected $description = 'Flag overdue orders, notify customers, and issue goodwill vouchers';

    public function handle(SendOrderDelayedEmail $mailer): int
    {
        $orders = Order::query()
            ->with('customer')
            ->where('is_delayed', false)
            ->whereNotNull('expected_delivery_at')
            ->where('expected_delivery_at', '<', now())
            ->whereNotIn('status', [
                OrderState::DELIVERED->value,
                OrderState::COMPLETED->value,
                OrderState::CANCELLED->value,
            ])
            ->get();

        foreach ($orders as $order) {
            $voucher = $this->goodwillVoucher($order);
            $order->update(['is_delayed' => true, 'delay_notified_at' => now()]);
            $mailer->execute($order, $voucher);
        }

        $this->info("Flagged {$orders->count()} delayed order(s).");

        return self::SUCCESS;
    }

    /** A single-use percentage voucher as compensation, if goodwill is enabled. */
    private function goodwillVoucher(Order $order): ?Voucher
    {
        $percent = (float) config('shop.delay_goodwill_percent', 10);
        if ($percent <= 0) {
            return null;
        }

        return Voucher::create([
            'code' => 'SORRY'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT).strtoupper(Str::random(3)),
            'type' => Voucher::TYPE_PERCENT,
            'value' => $percent,
            'usage_limit' => 1,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'description' => "Goodwill — delayed order {$order->order_number}",
        ]);
    }
}
