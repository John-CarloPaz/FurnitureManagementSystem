<?php

namespace App\Domain\Manufacturing;

use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Manufacturing\Listeners\SeedManufacturingStages;
use App\Domain\Manufacturing\Support\ProductionProgress;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Events\OrderTransitioned;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\GuardRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ManufacturingServiceProvider extends ServiceProvider
{
    public function boot(GuardRegistry $guards): void
    {
        // Seed per-item stages when production starts.
        Event::listen(OrderTransitioned::class, SeedManufacturingStages::class);

        // FSM guards: production must finish before QC; QC must pass before delivery.
        $guards->register(
            OrderState::IN_PRODUCTION,
            OrderState::QUALITY_CHECK,
            fn (Order $order) => ProductionProgress::allItemsStageDone($order, StageType::FINISHING)
                ? true
                : 'All items must complete the Finishing stage before QC.',
        );

        $guards->register(
            OrderState::QUALITY_CHECK,
            OrderState::READY_FOR_DELIVERY,
            fn (Order $order) => ProductionProgress::allItemsQcPassed($order)
                ? true
                : 'Every item must pass QC before the order is ready for delivery.',
        );
    }
}
