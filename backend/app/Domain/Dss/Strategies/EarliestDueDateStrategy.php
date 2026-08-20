<?php

namespace App\Domain\Dss\Strategies;

use App\Domain\Dss\Contracts\SchedulingStrategy;

/**
 * Earliest-Due-Date sequencing: produce the most time-critical items first.
 * A different objective (e.g. shortest processing time) is a new class, not an edit here.
 */
class EarliestDueDateStrategy implements SchedulingStrategy
{
    public function name(): string
    {
        return 'Earliest Due Date';
    }

    public function schedule(array $jobs): array
    {
        usort($jobs, fn ($a, $b) => $a->dueAt->getTimestamp() <=> $b->dueAt->getTimestamp());

        foreach ($jobs as $i => $job) {
            $job->sequence = $i + 1;
        }

        return $jobs;
    }
}
