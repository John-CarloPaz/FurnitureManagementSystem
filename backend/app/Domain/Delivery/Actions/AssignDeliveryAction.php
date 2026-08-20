<?php

namespace App\Domain\Delivery\Actions;

use App\Domain\Delivery\Events\DeliveryUpdated;
use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Logistics assigns (or reassigns) an order to a driver for delivery. */
class AssignDeliveryAction
{
    public function execute(Order $order, User $coordinator, ?int $driverId = null, ?string $batchLabel = null): DeliveryAssignment
    {
        if ($order->status !== OrderState::READY_FOR_DELIVERY) {
            throw ValidationException::withMessages([
                'order' => ['Only orders that are Ready for Delivery can be assigned.'],
            ]);
        }

        $assignment = $order->deliveryAssignment()->firstOrNew([]);
        $assignment->fill([
            'coordinator_id' => $coordinator->id,
            'driver_id' => $driverId,
            'batch_label' => $batchLabel,
            'assigned_at' => now(),
        ]);
        $assignment->save();

        event(new DeliveryUpdated($assignment));

        return $assignment->refresh();
    }
}
