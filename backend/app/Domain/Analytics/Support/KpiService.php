<?php

namespace App\Domain\Analytics\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes headline KPIs from live operational data (orders, manufacturing_stages,
 * deliveries). Time math is done in PHP (not driver-specific SQL) so the same code
 * runs on SQLite, MySQL and Postgres. All read-only.
 */
class KpiService
{
    /** @return array<string, float|null> */
    public function headline(): array
    {
        return [
            'ote' => $this->ote(),
            'avg_lead_time_days' => $this->avgLeadTimeDays(),
            'on_time_rate' => $this->onTimeRate(),
            'defect_rate' => $this->defectRate(),
        ];
    }

    /** Average days from placement to delivery over delivered orders. */
    public function avgLeadTimeDays(): ?float
    {
        $orders = DB::table('orders')
            ->whereNotNull('delivered_at')
            ->whereNotNull('placed_at')
            ->get(['placed_at', 'delivered_at']);

        if ($orders->isEmpty()) {
            return null;
        }

        $avgDays = $orders->avg(fn ($o) => (strtotime((string) $o->delivered_at) - strtotime((string) $o->placed_at)) / 86400);

        return round((float) $avgDays, 1);
    }

    /** % of delivered orders delivered within the target lead time. */
    public function onTimeRate(): ?float
    {
        $target = (int) config('analytics.target_lead_days', 14);

        $orders = DB::table('orders')
            ->whereNotNull('delivered_at')
            ->whereNotNull('placed_at')
            ->get(['placed_at', 'delivered_at']);

        if ($orders->isEmpty()) {
            return null;
        }

        $onTime = $orders->filter(
            fn ($o) => strtotime((string) $o->delivered_at) <= strtotime((string) $o->placed_at) + $target * 86400,
        )->count();

        return round(($onTime / $orders->count()) * 100, 1);
    }

    /** % of QC checks that failed. */
    public function defectRate(): float
    {
        $base = DB::table('manufacturing_stages')->where('stage', 'qc')->whereNotNull('qc_passed');
        $total = (clone $base)->count();

        if ($total === 0) {
            return 0.0;
        }

        $failed = (clone $base)->where('qc_passed', false)->count();

        return round(($failed / $total) * 100, 1);
    }

    /** Simplified OTE = performance (expected/actual time) × quality (1 − defect rate). Availability assumed 100%. */
    public function ote(): ?float
    {
        $rows = DB::table('manufacturing_stages')
            ->where('status', 'done')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('expected_minutes', '>', 0)
            ->get(['expected_minutes', 'started_at', 'ended_at']);

        $expected = 0.0;
        $actual = 0.0;
        foreach ($rows as $r) {
            $expected += (float) $r->expected_minutes;
            $actual += (strtotime((string) $r->ended_at) - strtotime((string) $r->started_at)) / 60;
        }

        if ($actual <= 0) {
            return null;
        }

        $performance = min(1.0, $expected / $actual);
        $quality = 1 - ($this->defectRate() / 100);

        return round($performance * $quality * 100, 1);
    }

    /** @return array<string, int> */
    public function ordersByStatus(): array
    {
        return DB::table('orders')
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [(string) $r->status => (int) $r->c])
            ->all();
    }

    /**
     * The stage causing the most delays, with a per-stage breakdown.
     *
     * @return array<string, mixed>|null
     */
    public function bottleneck(): ?array
    {
        $rows = DB::table('manufacturing_stages')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->get(['stage', 'is_delayed', 'started_at', 'ended_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        /** @var Collection<int, array{stage: string, delayed: int, avg_minutes: float}> $breakdown */
        $breakdown = $rows->groupBy('stage')->map(function (Collection $group, $stage) {
            $avgMinutes = $group->avg(
                fn ($r) => (strtotime((string) $r->ended_at) - strtotime((string) $r->started_at)) / 60,
            );

            return [
                'stage' => (string) $stage,
                'delayed' => $group->filter(fn ($r) => (bool) $r->is_delayed)->count(),
                'avg_minutes' => round((float) $avgMinutes, 1),
            ];
        })->values();

        $top = $breakdown->sortByDesc('delayed')->first();

        return [
            'stage' => $top['stage'],
            'delayed_count' => $top['delayed'],
            'avg_minutes' => $top['avg_minutes'],
            'breakdown' => $breakdown->all(),
        ];
    }

    /** @return array<string, int> */
    public function deliveriesByStatus(): array
    {
        return DB::table('delivery_assignments')
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [(string) $r->status => (int) $r->c])
            ->all();
    }
}
