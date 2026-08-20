<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Enums\OrderState;

class OrderStateFactory
{
    public static function for(OrderState $state): OrderStateContract
    {
        return match ($state) {
            OrderState::PLACED => new PlacedState,
            OrderState::CONFIRMED => new ConfirmedState,
            OrderState::IN_PRODUCTION => new InProductionState,
            OrderState::QUALITY_CHECK => new QualityCheckState,
            OrderState::REWORK => new ReworkState,
            OrderState::READY_FOR_DELIVERY => new ReadyForDeliveryState,
            OrderState::OUT_FOR_DELIVERY => new OutForDeliveryState,
            OrderState::DELIVERED => new DeliveredState,
            OrderState::COMPLETED => new CompletedState,
            OrderState::CANCELLED => new CancelledState,
        };
    }
}
