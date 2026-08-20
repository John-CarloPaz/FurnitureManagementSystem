<?php

namespace App\Http\Controllers\Api;

use App\Domain\Manufacturing\Actions\CompleteStageAction;
use App\Domain\Manufacturing\Actions\StartStageAction;
use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Manufacturing\Models\ManufacturingStage;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manufacturing\CompleteStageRequest;
use App\Http\Resources\ManufacturingStageResource;
use App\Http\Resources\ShopFloorOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ManufacturingController extends Controller
{
    /** Live shop-floor: all orders currently in production/QC/rework with item progress. */
    public function shopFloor(): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->whereIn('status', [
                OrderState::IN_PRODUCTION->value,
                OrderState::QUALITY_CHECK->value,
                OrderState::REWORK->value,
            ])
            ->with(['customer', 'items.stages'])
            ->latest()
            ->get();

        return ShopFloorOrderResource::collection($orders);
    }

    /** Production detail for a single order. */
    public function production(Order $order): ShopFloorOrderResource
    {
        return new ShopFloorOrderResource($order->load(['customer', 'items.stages']));
    }

    public function startStage(OrderItem $orderItem, string $stage, StartStageAction $action): ManufacturingStageResource
    {
        return new ManufacturingStageResource(
            $action->execute($this->resolveStage($orderItem, $stage), request()->user()),
        );
    }

    public function completeStage(CompleteStageRequest $request, OrderItem $orderItem, string $stage, CompleteStageAction $action): ManufacturingStageResource
    {
        return new ManufacturingStageResource(
            $action->execute(
                $this->resolveStage($orderItem, $stage),
                $request->user(),
                $request->has('qc_passed') ? $request->boolean('qc_passed') : null,
                $request->validated('notes'),
            ),
        );
    }

    public function flagStage(Request $request, OrderItem $orderItem, string $stage): ManufacturingStageResource
    {
        $validated = $request->validate(['notes' => ['required', 'string', 'max:500']]);

        $manufacturingStage = $this->resolveStage($orderItem, $stage);
        $manufacturingStage->update(['status' => StageStatus::BLOCKED, 'notes' => $validated['notes']]);

        return new ManufacturingStageResource($manufacturingStage->refresh());
    }

    private function resolveStage(OrderItem $item, string $stage): ManufacturingStage
    {
        $type = StageType::tryFrom($stage);
        abort_if($type === null, 404);

        // QC stages are owned by QA/PM; production stages by operatives/PM.
        $permission = $type->isQc() ? 'manufacturing.verify' : 'manufacturing.stage.update';
        abort_unless(request()->user()?->can($permission), 403);

        return $item->stages()->where('stage', $type->value)->firstOrFail();
    }
}
