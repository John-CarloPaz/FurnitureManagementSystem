<?php

namespace App\Http\Controllers\Api;

use App\Domain\Orders\Actions\PlaceOrderAction;
use App\Domain\Orders\Actions\RecordPaymentAction;
use App\Domain\Orders\Actions\TransitionOrderAction;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\PlaceOrderRequest;
use App\Http\Requests\Orders\RecordPaymentRequest;
use App\Http\Requests\Orders\TransitionOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderTransitionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /** List orders (customers see only their own). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()->with(['customer', 'items'])->latest();

        if (! $request->user()?->can('orders.viewAny')) {
            $query->where('customer_id', $request->user()?->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return OrderResource::collection($query->paginate(20));
    }

    /** Customer places a multi-item order against published products. */
    public function store(PlaceOrderRequest $request, PlaceOrderAction $action): JsonResponse
    {
        $this->authorize('place', Order::class);

        $order = $action->execute(
            $request->user(),
            $request->validated('items'),
            $request->validated('delivery_address'),
            $request->validated('notes'),
        );

        return (new OrderResource($order->load(['customer', 'items'])))->response()->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load([
            'customer', 'items', 'transitions.actor', 'payments',
            'deliveryAssignment.driver', 'deliveryAssignment.events.creator', 'deliveryAssignment.proof',
        ]));
    }

    public function transition(TransitionOrderRequest $request, Order $order, TransitionOrderAction $action): OrderResource
    {
        $to = OrderState::from($request->validated('to'));
        $this->authorize('transition', [$order, $to]);

        $order = $action->execute($order, $to, $request->user(), $request->validated('note'));

        return new OrderResource($order->load(['customer', 'items', 'transitions.actor', 'payments']));
    }

    public function transitions(Order $order): AnonymousResourceCollection
    {
        $this->authorize('view', $order);

        return OrderTransitionResource::collection($order->transitions()->with('actor')->get());
    }

    /** Record a payment (admin) — updates amount_paid + payment_status. */
    public function payments(RecordPaymentRequest $request, Order $order, RecordPaymentAction $action): OrderResource
    {
        $this->authorize('recordPayment', $order);

        $order = $action->execute(
            $order,
            (float) $request->validated('amount'),
            $request->validated('method'),
            $request->user(),
            $request->validated('note'),
        );

        return new OrderResource($order->load(['customer', 'items', 'payments']));
    }
}
