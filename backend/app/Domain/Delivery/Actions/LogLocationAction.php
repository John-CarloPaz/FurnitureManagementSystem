<?php

namespace App\Domain\Delivery\Actions;

use App\Domain\Delivery\Enums\DeliveryEventType;
use App\Domain\Delivery\Events\DeliveryUpdated;
use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Delivery\Models\DeliveryEvent;
use App\Models\User;

/** Driver logs a location update while en route (GPS or manual). */
class LogLocationAction
{
    public function execute(DeliveryAssignment $assignment, User $driver, ?float $lat = null, ?float $lng = null, ?string $manualLocation = null, ?string $note = null): DeliveryEvent
    {
        $event = $assignment->events()->create([
            'type' => DeliveryEventType::LOCATION,
            'lat' => $lat,
            'lng' => $lng,
            'manual_location' => $manualLocation,
            'note' => $note,
            'created_by' => $driver->id,
        ]);

        event(new DeliveryUpdated($assignment));

        return $event;
    }
}
