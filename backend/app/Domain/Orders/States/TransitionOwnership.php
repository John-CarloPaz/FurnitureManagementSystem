<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

/**
 * Which role(s) may perform each order transition — the real-world production
 * hand-offs (see docs/design/FSM.md §2). `admin` may perform any transition.
 * Consulted by OrderPolicy; keeps role rules out of the state classes.
 */
class TransitionOwnership
{
    /** @var array<string, array<int, string>> */
    private const MAP = [
        'PLACED>CONFIRMED' => ['admin'],
        'PLACED>CANCELLED' => ['admin', 'customer'],
        'CONFIRMED>IN_PRODUCTION' => ['production_manager'],
        'CONFIRMED>CANCELLED' => ['admin'],
        'IN_PRODUCTION>QUALITY_CHECK' => ['production_manager'],
        'QUALITY_CHECK>READY_FOR_DELIVERY' => ['qa_tester'],
        'QUALITY_CHECK>REWORK' => ['qa_tester'],
        'REWORK>IN_PRODUCTION' => ['production_manager'],
        'READY_FOR_DELIVERY>OUT_FOR_DELIVERY' => ['logistics_coordinator', 'delivery_personnel'],
        'OUT_FOR_DELIVERY>DELIVERED' => ['delivery_personnel'],
        'DELIVERED>COMPLETED' => ['admin'],
    ];

    /** @return array<int, string> roles allowed to perform from->to (empty = none besides admin). */
    public static function rolesFor(OrderState $from, OrderState $to): array
    {
        return self::MAP[$from->value.'>'.$to->value] ?? [];
    }

    public static function customerMayTransition(OrderState $from, OrderState $to): bool
    {
        return in_array('customer', self::rolesFor($from, $to), true);
    }
}
