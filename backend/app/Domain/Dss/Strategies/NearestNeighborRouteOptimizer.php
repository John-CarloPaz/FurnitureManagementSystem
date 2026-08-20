<?php

namespace App\Domain\Dss\Strategies;

use App\Domain\Dss\Contracts\RouteOptimizer;
use App\Domain\Dss\Support\RouteStop;

/** Greedy nearest-neighbour route over stops (haversine distance), from an optional depot. */
class NearestNeighborRouteOptimizer implements RouteOptimizer
{
    public function name(): string
    {
        return 'Nearest Neighbour';
    }

    public function optimize(array $stops, ?RouteStop $start = null): array
    {
        $remaining = array_values($stops);
        $route = [];
        $totalKm = 0.0;

        $current = $start;
        if ($current === null && $remaining !== []) {
            $current = array_shift($remaining);
            $route[] = $current;
        }

        while ($remaining !== [] && $current !== null) {
            $bestIdx = 0;
            $bestDist = INF;
            foreach ($remaining as $idx => $stop) {
                $d = $this->haversine($current->lat, $current->lng, $stop->lat, $stop->lng);
                if ($d < $bestDist) {
                    $bestDist = $d;
                    $bestIdx = $idx;
                }
            }

            $current = $remaining[$bestIdx];
            unset($remaining[$bestIdx]);
            $remaining = array_values($remaining);

            $totalKm += $bestDist;
            $route[] = $current;
        }

        return ['order' => $route, 'total_km' => round($totalKm, 2)];
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * asin(min(1.0, sqrt($a)));
    }
}
