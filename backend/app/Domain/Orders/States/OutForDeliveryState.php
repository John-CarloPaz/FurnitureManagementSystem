<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class OutForDeliveryState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::OUT_FOR_DELIVERY;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::DELIVERED];
    }
}
