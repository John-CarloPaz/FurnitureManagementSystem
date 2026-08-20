<?php

namespace App\Domain\Manufacturing\Listeners;

use App\Domain\Manufacturing\Actions\InitializeProductionAction;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Events\OrderTransitioned;

/** When an order enters production, seed each item's manufacturing stages. */
class SeedManufacturingStages
{
    public function __construct(private readonly InitializeProductionAction $action) {}

    public function handle(OrderTransitioned $event): void
    {
        if ($event->to === OrderState::IN_PRODUCTION) {
            $this->action->execute($event->order->load('items'));
        }
    }
}
