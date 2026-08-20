<?php

namespace App\Http\Controllers\Api;

use App\Domain\Analytics\Support\KpiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(private readonly KpiService $kpi) {}

    /** Full KPI dashboard (kpi.view — admin/QA). */
    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => [
            'headline' => $this->kpi->headline(),
            'orders_by_status' => $this->kpi->ordersByStatus(),
            'bottleneck' => $this->kpi->bottleneck(),
            'deliveries_by_status' => $this->kpi->deliveriesByStatus(),
        ]]);
    }

    /** Shop-floor KPIs (kpi.view.shopfloor — production manager). */
    public function shopFloor(): JsonResponse
    {
        return response()->json(['data' => [
            'ote' => $this->kpi->ote(),
            'defect_rate' => $this->kpi->defectRate(),
            'bottleneck' => $this->kpi->bottleneck(),
        ]]);
    }

    /** Delivery KPIs (kpi.view.delivery — logistics coordinator). */
    public function delivery(): JsonResponse
    {
        return response()->json(['data' => [
            'on_time_rate' => $this->kpi->onTimeRate(),
            'avg_lead_time_days' => $this->kpi->avgLeadTimeDays(),
            'deliveries_by_status' => $this->kpi->deliveriesByStatus(),
        ]]);
    }
}
