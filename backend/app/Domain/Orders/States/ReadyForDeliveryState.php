<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class ReadyForDeliveryState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::READY_FOR_DELIVERY;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::OUT_FOR_DELIVERY];
    }
}
