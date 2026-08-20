<?php

namespace App\Domain\Analytics\Support;

use Illuminate\Support\Facades\DB;

/**
 * Computes headline KPIs from live operational data (orders, manufacturing_stages,
 * deliveries). Postgres-specific aggregates (FILTER, EXTRACT EPOCH). All read-only.
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
        $v = DB::table('orders')
            ->whereNotNull('delivered_at')
            ->whereNotNull('placed_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (delivered_at - placed_at)) / 86400) as d')
            ->value('d');

        return $v !== null ? round((float) $v, 1) : null;
    }

    /** % of delivered orders delivered within the target lead time. */
    public function onTimeRate(): ?float
    {
        $target = (int) config('analytics.target_lead_days', 14);

        $row = DB::table('orders')
            ->whereNotNull('delivered_at')
            ->whereNotNull('placed_at')
            ->selectRaw("COUNT(*) as total, COUNT(*) FILTER (WHERE delivered_at <= placed_at + (interval '1 day' * ?)) as on_time", [$target])
            ->first();

        $total = (int) ($row->total ?? 0);

        return $total > 0 ? round(((int) $row->on_time / $total) * 100, 1) : null;
    }

    /** % of QC checks that failed. */
    public function defectRate(): float
    {
        $row = DB::table('manufacturing_stages')
            ->where('stage', 'qc')
            ->whereNotNull('qc_passed')
            ->selectRaw('COUNT(*) as total, COUNT(*) FILTER (WHERE qc_passed = false) as failed')
            ->first();

        $total = (int) ($row->total ?? 0);

        return $total > 0 ? round(((int) $row->failed / $total) * 100, 1) : 0.0;
    }

    /** Simplified OTE = performance (expected/actual time) × quality (1 − defect rate). Availability assumed 100%. */
    public function ote(): ?float
    {
        $row = DB::table('manufacturing_stages')
            ->where('status', 'done')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('expected_minutes', '>', 0)
            ->selectRaw('SUM(expected_minutes) as exp, SUM(EXTRACT(EPOCH FROM (ended_at - started_at)) / 60) as act')
            ->first();

        $act = (float) ($row->act ?? 0);
        if ($act <= 0) {
            return null;
        }

        $performance = min(1.0, (float) $row->exp / $act);
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
            ->mapWithKeys(fn ($r) => [$r->status => (int) $r->c])
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
            ->selectRaw('stage, COUNT(*) FILTER (WHERE is_delayed) as delayed, ROUND(AVG(EXTRACT(EPOCH FROM (ended_at - started_at)) / 60)::numeric, 1) as avg_min')
            ->groupBy('stage')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $breakdown = $rows->map(fn ($r) => [
            'stage' => $r->stage,
            'delayed' => (int) $r->delayed,
            'avg_minutes' => (float) $r->avg_min,
        ])->values()->all();

        $top = $rows->sortByDesc('delayed')->first();

        return [
            'stage' => $top->stage,
            'delayed_count' => (int) $top->delayed,
            'avg_minutes' => (float) $top->avg_min,
            'breakdown' => $breakdown,
        ];
    }

    /** @return array<string, int> */
    public function deliveriesByStatus(): array
    {
        return DB::table('delivery_assignments')
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->status => (int) $r->c])
            ->all();
    }
}
