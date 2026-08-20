<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class InProductionState extends AbstractOrderState
{
    public function state(): OrderState
    {
        return OrderState::IN_PRODUCTION;
    }

    public function allowedTransitions(): array
    {
        return [OrderState::QUALITY_CHECK];
    }
}
