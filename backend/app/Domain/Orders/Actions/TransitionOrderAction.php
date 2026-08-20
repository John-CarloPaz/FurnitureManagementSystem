<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Events\OrderTransitioned;
use App\Domain\Orders\Exceptions\InvalidTransitionException;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\GuardRegistry;
use App\Domain\Orders\States\OrderStateFactory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for changing an order's fulfillment state: validates the
 * graph (state class), runs data guards, persists the change + audit row + stamps
 * milestone timestamps, and emits the domain event — atomically. Role ownership
 * is enforced upstream by OrderPolicy.
 */
class TransitionOrderAction
{
    public function __construct(private readonly GuardRegistry $guards) {}

    public function execute(Order $order, OrderState $to, ?User $actor = null, ?string $note = null): Order
    {
        $from = $order->status;
        $current = OrderStateFactory::for($from);

        if (! $current->canTransitionTo($to)) {
            throw new InvalidTransitionException($from, $to);
        }

        $this->guards->assert($order, $from, $to);

        return DB::transaction(function () use ($order, $from, $to, $actor, $note) {
            $changes = ['status' => $to];
            if ($to === OrderState::CONFIRMED) {
                $changes['confirmed_at'] = now();
            }
            if ($to === OrderState::DELIVERED) {
                $changes['delivered_at'] = now();
            }
            $order->update($changes);

            $order->transitions()->create([
                'from_state' => $from,
                'to_state' => $to,
                'actor_id' => $actor?->id,
                'note' => $note,
            ]);

            event(new OrderTransitioned($order, $from, $to, $actor));

            return $order->refresh();
        });
    }
}
