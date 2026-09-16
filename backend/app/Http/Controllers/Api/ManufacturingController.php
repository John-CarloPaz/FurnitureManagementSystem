<?php

namespace App\Http\Controllers\Api;

use App\Domain\Manufacturing\Actions\CompleteStageAction;
use App\Domain\Manufacturing\Actions\RecordQualityInspectionAction;
use App\Domain\Manufacturing\Actions\StartStageAction;
use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Manufacturing\Models\ManufacturingStage;
use App\Domain\Manufacturing\Models\QualityInspectionPhoto;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manufacturing\CompleteStageRequest;
use App\Http\Requests\Manufacturing\RecordQualityInspectionRequest;
use App\Http\Resources\ManufacturingStageResource;
use App\Http\Resources\QualityInspectionResource;
use App\Http\Resources\ShopFloorOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->with(['customer', 'items.stages', 'items.qualityInspections.photos', 'items.qualityInspections.inspector'])
            ->latest()
            ->get();

        return ShopFloorOrderResource::collection($orders);
    }

    /** Production detail for a single order. */
    public function production(Order $order): ShopFloorOrderResource
    {
        return new ShopFloorOrderResource(
            $order->load(['customer', 'items.stages', 'items.qualityInspections.photos', 'items.qualityInspections.inspector']),
        );
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

    /** QC verdict for an item. A fail captures reason + photos, resets its stages, and reworks the order. */
    public function qc(RecordQualityInspectionRequest $request, OrderItem $orderItem, RecordQualityInspectionAction $action): QualityInspectionResource
    {
        abort_unless($request->user()?->can('manufacturing.verify'), 403);

        $photos = $request->file('photos');

        return new QualityInspectionResource($action->execute(
            $orderItem,
            $request->user(),
            $request->boolean('passed'),
            $request->validated('reason'),
            is_array($photos) ? $photos : [],
        ));
    }

    /** Stream a QC defect photo — protected by the signed URL only. */
    public function photo(QualityInspectionPhoto $photo): StreamedResponse
    {
        return Storage::disk('local')->download($photo->path);
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
