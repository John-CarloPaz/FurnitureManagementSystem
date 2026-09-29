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
     * @param  array<int, array{product_id:int, quantity:int}>  $items
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

            foreach ($items as $line) {
                $product = Product::find($line['product_id']);
                if (! $product || $product->status !== ProductStatus::PUBLISHED) {
                    throw ValidationException::withMessages([
                        'items' => ["Product #{$line['product_id']} is not available."],
                    ]);
                }

                $qty = max(1, (int) $line['quantity']);
                $lineTotal = (float) $product->base_price * $qty;
                $subtotal += $lineTotal;

                $rows[] = [
                    'product_id' => $product->id,
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
