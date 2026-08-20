<?php

namespace App\Http\Controllers\Api;

use App\Domain\Delivery\Actions\AssignDeliveryAction;
use App\Domain\Delivery\Actions\DispatchDeliveryAction;
use App\Domain\Delivery\Actions\LogLocationAction;
use App\Domain\Delivery\Actions\RecordProofOfDeliveryAction;
use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Delivery\Models\ProofOfDelivery;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\AssignDeliveryRequest;
use App\Http\Requests\Delivery\DeliveryEventRequest;
use App\Http\Requests\Delivery\ProofOfDeliveryRequest;
use App\Http\Resources\DeliveryAssignmentResource;
use App\Http\Resources\OrderResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryController extends Controller
{
    /** Delivery assignments (coordinators/admin see all; drivers see their own). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DeliveryAssignment::class);

        $query = DeliveryAssignment::query()->with(['order.customer', 'driver'])->latest();

        $user = $request->user();
        if (! $user?->hasRole('admin') && ! $user?->can('delivery.assign')) {
            $query->where('driver_id', $user?->id);
        }

        return DeliveryAssignmentResource::collection($query->get());
    }

    /** Delivery personnel a coordinator can assign to (scoped to delivery.assign). */
    public function drivers(): JsonResponse
    {
        $this->authorize('assign', DeliveryAssignment::class);

        return response()->json([
            'data' => User::role('delivery_personnel')
                ->where('is_active', true)
                ->get(['id', 'name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    /** Orders ready for delivery that still need a driver (coordinator board). */
    public function unassigned(): AnonymousResourceCollection
    {
        $this->authorize('assign', DeliveryAssignment::class);

        $orders = Order::query()
            ->where('status', OrderState::READY_FOR_DELIVERY->value)
            ->whereDoesntHave('deliveryAssignment')
            ->with('customer')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(AssignDeliveryRequest $request, Order $order, AssignDeliveryAction $action): JsonResponse
    {
        $this->authorize('assign', DeliveryAssignment::class);

        $assignment = $action->execute(
            $order,
            $request->user(),
            $request->validated('driver_id') ? (int) $request->validated('driver_id') : null,
            $request->validated('batch_label'),
        );

        return (new DeliveryAssignmentResource($this->full($assignment)))->response()->setStatusCode(201);
    }

    public function show(DeliveryAssignment $assignment): DeliveryAssignmentResource
    {
        $this->authorize('view', $assignment);

        return new DeliveryAssignmentResource($this->full($assignment));
    }

    public function dispatchDelivery(DeliveryEventRequest $request, DeliveryAssignment $assignment, DispatchDeliveryAction $action): DeliveryAssignmentResource
    {
        $this->authorize('update', $assignment);

        $action->execute(
            $assignment,
            $request->user(),
            $request->validated('lat') !== null ? (float) $request->validated('lat') : null,
            $request->validated('lng') !== null ? (float) $request->validated('lng') : null,
            $request->validated('manual_location'),
        );

        return new DeliveryAssignmentResource($this->full($assignment));
    }

    public function location(DeliveryEventRequest $request, DeliveryAssignment $assignment, LogLocationAction $action): DeliveryAssignmentResource
    {
        $this->authorize('update', $assignment);

        $action->execute(
            $assignment,
            $request->user(),
            $request->validated('lat') !== null ? (float) $request->validated('lat') : null,
            $request->validated('lng') !== null ? (float) $request->validated('lng') : null,
            $request->validated('manual_location'),
            $request->validated('note'),
        );

        return new DeliveryAssignmentResource($this->full($assignment));
    }

    public function proof(ProofOfDeliveryRequest $request, DeliveryAssignment $assignment, RecordProofOfDeliveryAction $action): DeliveryAssignmentResource
    {
        $this->authorize('recordProof', $assignment);

        $action->execute(
            $assignment,
            $request->user(),
            $request->file('photo'),
            $request->validated('recipient_name'),
        );

        return new DeliveryAssignmentResource($this->full($assignment));
    }

    /** Stream a proof photo — protected by the signed URL only. */
    public function file(ProofOfDelivery $proof): StreamedResponse
    {
        return Storage::disk('local')->download($proof->photo_path);
    }

    private function full(DeliveryAssignment $assignment): DeliveryAssignment
    {
        return $assignment->load(['order.customer', 'driver', 'coordinator', 'events.creator', 'proof']);
    }
}
