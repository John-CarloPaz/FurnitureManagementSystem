<?php

namespace App\Domain\Delivery\Enums;

/** A logged step in a delivery's journey. */
enum DeliveryEventType: string
{
    case PICKED_UP = 'picked_up';
    case LOCATION = 'location';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
