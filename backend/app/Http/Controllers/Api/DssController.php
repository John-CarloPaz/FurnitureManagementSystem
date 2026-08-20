<?php

namespace App\Http\Controllers\Api;

use App\Domain\Dss\Contracts\RouteOptimizer;
use App\Domain\Dss\Contracts\SchedulingStrategy;
use App\Domain\Dss\Support\ProductionJob;
use App\Domain\Dss\Support\RouteStop;
use App\Domain\Dss\Support\WorkflowAnalyzer;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dss\RouteOptimizeRequest;
use Illuminate\Http\JsonResponse;

class DssController extends Controller
{
    /** Recommended production sequence for confirmed orders (Model-Driven DSS). */
    public function schedule(SchedulingStrategy $strategy): JsonResponse
    {
        $default = (int) config('analytics.target_lead_days', 14);
        $jobs = [];

        $orders = Order::query()
            ->where('status', OrderState::CONFIRMED->value)
            ->with('items.product')
            ->get();

        foreach ($orders as $order) {
            $placed = $order->placed_at ?? $order->created_at;
            foreach ($order->items as $item) {
                $lead = $item->product->lead_time_days ?? $default;
                $jobs[] = new ProductionJob(
                    $item->id,
                    $order->order_number,
                    $item->product_name,
                    $placed->copy()->addDays($lead),
                    $lead,
                );
            }
        }

        $ordered = $strategy->schedule($jobs);

        return response()->json(['data' => [
            'strategy' => $strategy->name(),
            'plan' => array_map(fn (ProductionJob $j) => $j->toArray(), $ordered),
        ]]);
    }

    /** Workflow bottleneck + recommendation. */
    public function bottlenecks(WorkflowAnalyzer $analyzer): JsonResponse
    {
        return response()->json(['data' => $analyzer->analyze()]);
    }

    /** Optimize a delivery route over the provided stops. */
    public function routeOptimize(RouteOptimizeRequest $request, RouteOptimizer $optimizer): JsonResponse
    {
        $stops = array_map(
            fn (array $s) => new RouteStop($s['id'], $s['label'] ?? (string) $s['id'], (float) $s['lat'], (float) $s['lng']),
            $request->validated('stops'),
        );

        $start = $request->validated('start')
            ? new RouteStop('start', 'Depot', (float) $request->input('start.lat'), (float) $request->input('start.lng'))
            : null;

        $result = $optimizer->optimize($stops, $start);

        return response()->json(['data' => [
            'optimizer' => $optimizer->name(),
            'total_km' => $result['total_km'],
            'order' => array_map(fn (RouteStop $s) => $s->toArray(), $result['order']),
        ]]);
    }
}
