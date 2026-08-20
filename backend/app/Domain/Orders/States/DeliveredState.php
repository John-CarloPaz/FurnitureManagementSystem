<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class DeliveredState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::DELIVERED;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::COMPLETED];
    }
}
