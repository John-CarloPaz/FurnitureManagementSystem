<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class PlacedState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::PLACED;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::CONFIRMED, OrderState::CANCELLED];
    }
}
