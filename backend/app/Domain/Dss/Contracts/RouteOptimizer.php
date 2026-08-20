<?php

namespace App\Domain\Dss\Contracts;

use App\Domain\Dss\Support\RouteStop;

interface RouteOptimizer
{
    public function name(): string;

    /**
     * @param  array<int, RouteStop>  $stops
     * @return array{order: array<int, RouteStop>, total_km: float}
     */
    public function optimize(array $stops, ?RouteStop $start = null): array;
}
