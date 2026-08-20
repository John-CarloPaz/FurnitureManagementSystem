<?php

namespace App\Domain\Orders\Enums;

/**
 * Order FULFILLMENT states. See docs/design/FSM.md §2.
 * Role ownership of each transition lives in States\TransitionOwnership.
 */
enum OrderState: string
{
    case PLACED = 'PLACED';
    case CONFIRMED = 'CONFIRMED';
    case IN_PRODUCTION = 'IN_PRODUCTION';
    case QUALITY_CHECK = 'QUALITY_CHECK';
    case REWORK = 'REWORK';
    case READY_FOR_DELIVERY = 'READY_FOR_DELIVERY';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->value)));
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }
}
