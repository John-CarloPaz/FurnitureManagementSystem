<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Events\OrderPlaced;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Support\PricingCalculator;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrderAction
{
    public function __construct(private readonly ResolveVoucher $voucher) {}

    /**
     * @param  array<int, array{product_id:int, quantity:int, variation_id?:int|null}>  $items
     */
    public function execute(
        User $customer,
        array $items,
        ?string $deliveryAddress = null,
        ?string $notes = null,
        ?string $voucherCode = null,
        ?string $paymentMethod = null,
    ): Order {
        return DB::transaction(function () use ($customer, $items, $deliveryAddress, $notes, $voucherCode, $paymentMethod) {
            $subtotal = 0.0;
            $rows = [];
            $totalLeadDays = 0;

            foreach ($items as $line) {
                $product = Product::find($line['product_id']);
                if (! $product || $product->status !== ProductStatus::PUBLISHED) {
                    throw ValidationException::withMessages([
                        'items' => ["Product #{$line['product_id']} is not available."],
                    ]);
                }

                $qty = max(1, (int) $line['quantity']);
                // Production runs one piece at a time, so lead times stack: every unit of
                // every line adds its own build days to the promised delivery date.
                $totalLeadDays += (int) ($product->lead_time_days ?? 0) * $qty;
                $lineTotal = (float) $product->base_price * $qty;
                $subtotal += $lineTotal;

                // Record which colour variation was ordered (if any) for sales insights.
                $variationId = isset($line['variation_id']) ? (int) $line['variation_id'] : null;
                $variation = $variationId ? $product->variations()->whereKey($variationId)->first() : null;

                $rows[] = [
                    'product_id' => $product->id,
                    'variation_id' => $variation?->id,
                    'variation_name' => $variation?->name,
                    'product_name' => $product->name,
                    'unit_price' => $product->base_price,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            $resolved = $this->voucher->execute($voucherCode, $subtotal);
            if ($resolved['error'] !== null) {
                throw ValidationException::withMessages(['voucher_code' => [$resolved['error']]]);
            }

            $pricing = PricingCalculator::compute($subtotal, $resolved['discount']);
            $leadDays = $totalLeadDays > 0 ? $totalLeadDays : (int) config('analytics.target_lead_days', 14);

            $order = Order::create([
                'order_number' => 'PENDING',
                'customer_id' => $customer->id,
                'status' => OrderState::PLACED,
                'subtotal' => $pricing['subtotal'],
                'delivery_fee' => $pricing['shipping'],
                'tax_amount' => $pricing['tax'],
                'discount_amount' => $pricing['discount'],
                'voucher_id' => $resolved['voucher']?->id,
                'voucher_code' => $resolved['voucher']?->code,
                'total' => $pricing['total'],
                'payment_status' => 'UNPAID',
                'payment_method' => $paymentMethod ?: 'COD',
                'delivery_address' => $deliveryAddress,
                'notes' => $notes,
                'placed_at' => now(),
                'expected_delivery_at' => now()->addDays($leadDays),
            ]);

            $order->update(['order_number' => 'ORD-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT)]);
            $order->items()->createMany($rows);

            if ($resolved['voucher'] !== null) {
                $resolved['voucher']->increment('used_count');
            }
            $order->transitions()->create([
                'from_state' => null,
                'to_state' => OrderState::PLACED,
                'actor_id' => $customer->id,
                'note' => 'Order placed',
            ]);

            $order->refresh();
            event(new OrderPlaced($order));

            return $order;
        });
    }
}
