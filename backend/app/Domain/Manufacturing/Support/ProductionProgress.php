<?php

namespace App\Domain\Manufacturing\Support;

use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;

/** Aggregations over per-item manufacturing stages (used by FSM guards + UI). */
class ProductionProgress
{
    public static function allItemsStageDone(Order $order, StageType $type): bool
    {
        $order->loadMissing('items.stages');
        if ($order->items->isEmpty()) {
            return false;
        }

        foreach ($order->items as $item) {
            $stage = $item->stages->firstWhere('stage', $type);
            if (! $stage || $stage->status !== StageStatus::DONE) {
                return false;
            }
        }

        return true;
    }

    public static function allItemsQcPassed(Order $order): bool
    {
        $order->loadMissing('items.stages');
        if ($order->items->isEmpty()) {
            return false;
        }

        foreach ($order->items as $item) {
            $qc = $item->stages->firstWhere('stage', StageType::QC);
            if (! $qc || $qc->status !== StageStatus::DONE || $qc->qc_passed !== true) {
                return false;
            }
        }

        return true;
    }

    public static function itemPercent(OrderItem $item): int
    {
        $item->loadMissing('stages');
        $total = $item->stages->count();
        if ($total === 0) {
            return 0;
        }
        $done = $item->stages->where('status', StageStatus::DONE)->count();

        return (int) round($done / $total * 100);
    }
}
