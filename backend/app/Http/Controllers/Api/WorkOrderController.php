<?php

namespace App\Http\Controllers\Api;

use App\Domain\Manufacturing\Models\WorkOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manufacturing\StoreWorkOrderRequest;
use App\Http\Requests\Manufacturing\UpdateWorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkOrderController extends Controller
{
    /** List work orders; operatives see only their own. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = WorkOrder::query()->with(['orderItem', 'assignee'])->orderBy('sequence');

        // Operatives without assign rights see only what's assigned to them.
        if (! $request->user()?->can('workorders.assign')) {
            $query->where('assigned_to', $request->user()?->id);
        }

        return WorkOrderResource::collection($query->get());
    }

    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        $workOrder = WorkOrder::create($request->validated());

        return (new WorkOrderResource($workOrder->load(['orderItem', 'assignee'])))->response()->setStatusCode(201);
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): WorkOrderResource
    {
        $workOrder->update($request->validated());

        return new WorkOrderResource($workOrder->load(['orderItem', 'assignee']));
    }
}
