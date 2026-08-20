<?php

namespace App\Domain\Delivery\Enums;

/** Status of a delivery assignment. */
enum DeliveryStatus: string
{
    case ASSIGNED = 'assigned';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
