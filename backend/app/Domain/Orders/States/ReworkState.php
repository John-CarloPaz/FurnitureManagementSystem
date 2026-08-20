<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class ReworkState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::REWORK;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::IN_PRODUCTION];
    }
}
