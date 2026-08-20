<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class CancelledState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::CANCELLED;
    }

    public function allowedTransitions(): array
    {
        return [];
    }
}
