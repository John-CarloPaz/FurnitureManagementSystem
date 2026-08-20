<?php

namespace App\Domain\Notifications\Listeners;

use App\Domain\Notifications\Notifications\OrderStatusNotification;
use App\Domain\Orders\Events\OrderTransitioned;

/** Notify the customer whenever their order advances a stage. */
class NotifyOnOrderTransition
{
    public function handle(OrderTransitioned $event): void
    {
        $order = $event->order;

        $order->customer?->notify(
            new OrderStatusNotification($order->id, $order->order_number, $event->to->value),
        );
    }
}
