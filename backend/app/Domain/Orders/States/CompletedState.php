<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class CompletedState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::COMPLETED;
    }

    public function allowedTransitions(): array
    {
        return [];
    }
}
