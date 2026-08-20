<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class ConfirmedState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::CONFIRMED;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::IN_PRODUCTION, OrderState::CANCELLED];
    }
}
