<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class QualityCheckState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::QUALITY_CHECK;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::READY_FOR_DELIVERY, OrderState::REWORK];
    }
}
