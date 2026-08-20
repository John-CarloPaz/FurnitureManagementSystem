<?php

namespace App\Domain\Dss;

use App\Domain\Dss\Contracts\RouteOptimizer;
use App\Domain\Dss\Contracts\SchedulingStrategy;
use App\Domain\Dss\Strategies\EarliestDueDateStrategy;
use App\Domain\Dss\Strategies\NearestNeighborRouteOptimizer;
use Illuminate\Support\ServiceProvider;

/**
 * Binds DSS strategy interfaces to concrete algorithms. Changing the shop's
 * scheduling or routing approach = change one binding here (Dependency Inversion).
 */
class DssServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SchedulingStrategy::class, EarliestDueDateStrategy::class);
        $this->app->bind(RouteOptimizer::class, NearestNeighborRouteOptimizer::class);
    }
}
