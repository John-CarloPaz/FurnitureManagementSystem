<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Exceptions\InvalidTransitionException;
use App\Domain\Orders\Models\Order;

/**
 * Data-precondition guards for specific (from -> to) transitions, kept separate
 * from the transition graph (states) and from role rules (policies).
 * Modules register their own guards in their service provider — so adding a
 * guard never edits the FSM. A guard returns true to allow, or a string reason to block.
 */
class GuardRegistry
{
    /** @var array<string, callable(Order): (bool|string)> */
    private array $guards = [];

    public function register(OrderState $from, OrderState $to, callable $guard): void
    {
        $this->guards[$this->key($from, $to)] = $guard;
    }

    public function assert(Order $order, OrderState $from, OrderState $to): void
    {
        $guard = $this->guards[$this->key($from, $to)] ?? null;
        if ($guard === null) {
            return;
        }

        $result = $guard($order);
        if ($result !== true) {
            throw new InvalidTransitionException($from, $to, is_string($result) ? $result : null);
        }
    }

    private function key(OrderState $from, OrderState $to): string
    {
        return $from->value.'>'.$to->value;
    }
}
