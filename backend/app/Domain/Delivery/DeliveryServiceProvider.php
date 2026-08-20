<?php

namespace App\Domain\Delivery;

use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Delivery\Policies\DeliveryPolicy;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\GuardRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DeliveryServiceProvider extends ServiceProvider
{
    public function boot(GuardRegistry $guards): void
    {
        Gate::policy(DeliveryAssignment::class, DeliveryPolicy::class);

        // FSM guards: can't dispatch without an assignment; can't mark delivered without proof.
        $guards->register(
            OrderState::READY_FOR_DELIVERY,
            OrderState::OUT_FOR_DELIVERY,
            fn (Order $order) => $order->deliveryAssignment
                ? true
                : 'Assign the order to a driver before dispatching.',
        );

        $guards->register(
            OrderState::OUT_FOR_DELIVERY,
            OrderState::DELIVERED,
            fn (Order $order) => $order->deliveryAssignment?->proof()->exists()
                ? true
                : 'Capture proof of delivery before marking the order delivered.',
        );
    }
}
