<?php

namespace App\Domain\Delivery\Actions;

use App\Domain\Delivery\Enums\DeliveryEventType;
use App\Domain\Delivery\Enums\DeliveryStatus;
use App\Domain\Delivery\Events\DeliveryUpdated;
use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Orders\Actions\TransitionOrderAction;
use App\Domain\Orders\Enums\OrderState;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Driver picks up the order: logs pickup + moves the order to OUT_FOR_DELIVERY. */
class DispatchDeliveryAction
{
    public function __construct(private readonly TransitionOrderAction $transition) {}

    public function execute(DeliveryAssignment $assignment, User $driver, ?float $lat = null, ?float $lng = null, ?string $manualLocation = null): DeliveryAssignment
    {
        return DB::transaction(function () use ($assignment, $driver, $lat, $lng, $manualLocation) {
            $assignment->events()->create([
                'type' => DeliveryEventType::PICKED_UP,
                'lat' => $lat,
                'lng' => $lng,
                'manual_location' => $manualLocation,
                'created_by' => $driver->id,
            ]);

            $assignment->update(['status' => DeliveryStatus::OUT_FOR_DELIVERY]);

            $this->transition->execute(
                $assignment->order,
                OrderState::OUT_FOR_DELIVERY,
                $driver,
                'Picked up for delivery',
            );

            event(new DeliveryUpdated($assignment));

            return $assignment->refresh();
        });
    }
}
