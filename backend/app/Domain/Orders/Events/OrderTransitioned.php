<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired on every FSM transition. Notifications & Analytics subscribe to this
 * (M4/M5) — the Orders module never calls them directly. See docs/design/EVENTS.md.
 */
class OrderTransitioned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderState $from,
        public OrderState $to,
        public ?User $actor = null,
    ) {}
}
