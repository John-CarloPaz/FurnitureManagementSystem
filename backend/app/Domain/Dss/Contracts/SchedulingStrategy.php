<?php

namespace App\Domain\Dss\Contracts;

use App\Domain\Dss\Support\ProductionJob;

/**
 * A production-scheduling algorithm. Swap the bound implementation in
 * DssServiceProvider to change how the shop sequences work — no controller change.
 */
interface SchedulingStrategy
{
    public function name(): string;

    /**
     * @param  array<int, ProductionJob>  $jobs
     * @return array<int, ProductionJob> the same jobs, ordered, with sequence set
     */
    public function schedule(array $jobs): array;
}
