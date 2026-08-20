<?php

namespace App\Domain\Notifications\Listeners;

use App\Domain\Notifications\Notifications\NewOrderNotification;
use App\Domain\Notifications\Notifications\OrderStatusNotification;
use App\Domain\Orders\Events\OrderPlaced;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/** Notify the customer (confirmation) and admins (new order) when an order is placed. */
class NotifyOnOrderPlaced
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        $order->customer?->notify(
            new OrderStatusNotification($order->id, $order->order_number, $order->status->value),
        );

        Notification::send(
            User::role('admin')->get(),
            new NewOrderNotification($order->id, $order->order_number, $order->customer->name),
        );
    }
}
