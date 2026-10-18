<?php

namespace App\Domain\Analytics\Support;

use App\Domain\Manufacturing\Models\ManufacturingStage;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Products\Models\Product;
use Illuminate\Support\Collection;

/**
 * Per-product performance metrics for the catalog detail dashboard. Time/aggregate
 * math is done in PHP so it runs on SQLite, MySQL and Postgres alike.
 */
class ProductInsights
{
    /** @return array<string, mixed> */
    public function for(Product $product): array
    {
        $items = OrderItem::query()->where('product_id', $product->id)->get(['id', 'order_id', 'quantity', 'line_total']);
        $itemIds = $items->pluck('id')->all();
        $orderIds = $items->pluck('order_id')->unique()->all();

        /** @var Collection<int, Order> $orders */
        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->get(['id', 'status', 'delivered_at', 'expected_delivery_at', 'is_delayed'])
            ->keyBy('id');

        $soldItems = $items->filter(function (OrderItem $i) use ($orders): bool {
            $o = $orders->get($i->order_id);

            return $o !== null && $o->status !== OrderState::CANCELLED;
        });

        $active = $orders->filter(fn (Order $o): bool => $o->status !== OrderState::CANCELLED);
        $activeCount = $active->count();

        $delivered = $orders->filter(
            fn (Order $o): bool => in_array($o->status, [OrderState::DELIVERED, OrderState::COMPLETED], true) && $o->delivered_at !== null,
        );
        $onTime = $delivered->filter(
            fn (Order $o): bool => $o->delivered_at !== null && $o->expected_delivery_at !== null && $o->delivered_at->lte($o->expected_delivery_at),
        )->count();

        $qc = ManufacturingStage::query()
            ->whereIn('order_item_id', $itemIds)
            ->where('stage', 'qc')
            ->whereNotNull('qc_passed')
            ->get(['qc_passed']);
        $qcTotal = $qc->count();
        $qcFailed = $qc->filter(fn (ManufacturingStage $s): bool => $s->qc_passed === false)->count();

        $ratingAvg = $product->reviews()->avg('rating');

        return [
            'units_sold' => (int) $soldItems->sum('quantity'),
            'revenue' => round((float) $soldItems->sum(fn (OrderItem $i): float => (float) $i->line_total), 2),
            'orders_count' => $activeCount,
            'rating_avg' => $ratingAvg !== null ? round((float) $ratingAvg, 1) : null,
            'rating_count' => $product->reviews()->count(),
            'on_time_rate' => $delivered->count() > 0 ? round($onTime / $delivered->count() * 100, 1) : null,
            'delayed_rate' => $activeCount > 0 ? round($active->filter(fn (Order $o): bool => (bool) $o->is_delayed)->count() / $activeCount * 100, 1) : 0.0,
            'defect_rate' => $qcTotal > 0 ? round($qcFailed / $qcTotal * 100, 1) : 0.0,
            'orders_by_status' => $active->groupBy(fn (Order $o): string => $o->status->value)
                ->map(fn (Collection $g): int => $g->count())
                ->all(),
        ];
    }
}
