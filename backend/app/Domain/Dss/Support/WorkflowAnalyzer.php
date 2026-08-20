<?php

namespace App\Domain\Dss\Support;

use App\Domain\Analytics\Support\KpiService;

/** Turns the production bottleneck into an actionable recommendation. */
class WorkflowAnalyzer
{
    public function __construct(private readonly KpiService $kpi) {}

    /** @return array<string, mixed> */
    public function analyze(): array
    {
        $bottleneck = $this->kpi->bottleneck();

        $recommendation = $bottleneck
            ? sprintf(
                "'%s' is the biggest hold-up (%d delayed, avg %.1f min). Consider reallocating capacity here.",
                ucfirst($bottleneck['stage']),
                $bottleneck['delayed_count'],
                $bottleneck['avg_minutes'],
            )
            : 'Not enough completed production data to identify a bottleneck yet.';

        return ['bottleneck' => $bottleneck, 'recommendation' => $recommendation];
    }
}
