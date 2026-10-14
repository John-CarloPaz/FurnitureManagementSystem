<?php

namespace App\Domain\Dss\Support;

use App\Domain\Delivery\Enums\DeliveryStatus;
use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Model-driven DSS: after production, recommends when to deliver an order (from its lead
 * time / promised date) and which delivery person to assign (the least-loaded driver).
 */
class DeliveryRecommendation
{
    /**
     * @return array{recommended_date: string, reason: string, suggested_driver: array{id: int, name: string, active_deliveries: int}|null}
     */
    public function for(Order $order): array
    {
        [$date, $reason] = $this->recommendedDate($order);

        return [
            'recommended_date' => $date->toDateString(),
            'reason' => $reason,
            'suggested_driver' => $this->suggestedDriver(),
        ];
    }

    /** @return array{0: Carbon, 1: string} */
    private function recommendedDate(Order $order): array
    {
        $window = (int) config('shop.delivery_window_days', 3);
        $expected = $order->expected_delivery_at;

        if ($expected !== null && $expected->isPast()) {
            return [now()->addDay()->startOfDay(), 'This order is past its promised date — dispatch within 1 day to limit the delay.'];
        }

        $standard = now()->addDays($window)->startOfDay();

        if ($expected !== null && $expected->lt($standard)) {
            return [$expected->copy()->startOfDay(), 'Deliver by the promised date to stay on schedule.'];
        }

        return [$standard, "Standard {$window}-day delivery window after production."];
    }

    /**
     * The delivery person carrying the fewest active (not-yet-delivered) assignments.
     *
     * @return array{id: int, name: string, active_deliveries: int}|null
     */
    private function suggestedDriver(): ?array
    {
        $drivers = User::role('delivery_personnel')->get(['id', 'name']);

        $best = null;
        $fewest = PHP_INT_MAX;
        foreach ($drivers as $driver) {
            $active = DeliveryAssignment::query()
                ->where('driver_id', $driver->id)
                ->where('status', '!=', DeliveryStatus::DELIVERED->value)
                ->count();

            if ($active < $fewest) {
                $fewest = $active;
                $best = ['id' => $driver->id, 'name' => $driver->name, 'active_deliveries' => $active];
            }
        }

        return $best;
    }
}
