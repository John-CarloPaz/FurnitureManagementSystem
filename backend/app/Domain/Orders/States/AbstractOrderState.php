<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

abstract class AbstractOrderState implements OrderStateContract
{
    public function canTransitionTo(OrderState $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
