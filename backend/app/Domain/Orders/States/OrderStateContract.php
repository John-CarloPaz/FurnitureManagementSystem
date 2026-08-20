<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

/**
 * One class per FSM state (State pattern). Adding a state = adding a class,
 * never editing a switch (Open/Closed). See docs/design/FSM.md.
 */
interface OrderStateContract
{
    public function state(): OrderState;

    /** @return array<int, OrderState> */
    public function allowedTransitions(): array;

    public function canTransitionTo(OrderState $target): bool;
}
