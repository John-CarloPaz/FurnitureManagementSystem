<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the per-item manufacturing stages when an order enters production.
 * Idempotent — items that already have stages (e.g. on rework) are skipped.
 */
class InitializeProductionAction
{
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->stages()->exists()) {
                    continue;
                }
                foreach (StageType::ordered() as $stage) {
                    $item->stages()->create([
                        'stage' => $stage,
                        'status' => StageStatus::PENDING,
                        'expected_minutes' => $stage->defaultExpectedMinutes(),
                    ]);
                }
            }
        });
    }
}
