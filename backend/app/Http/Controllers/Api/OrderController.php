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
use Illuminate\Validation\ValidationException;

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
            $request->validated('voucher_code'),
            $request->validated('payment_method'),
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

    /** Customer pays for their own GCash/Bank order (simulated) — records the payment and marks it paid. */
    public function pay(Request $request, Order $order, RecordPaymentAction $action): OrderResource
    {
        abort_unless($order->customer_id === $request->user()?->id, 403);

        $validated = $request->validate(['reference' => ['nullable', 'string', 'max:100']]);

        if ($order->payment_status === 'PAID') {
            throw ValidationException::withMessages(['payment' => ['This order is already paid.']]);
        }
        if (! in_array($order->payment_method, ['GCASH', 'BANK'], true)) {
            throw ValidationException::withMessages(['payment' => ['This order is not payable online.']]);
        }

        $remaining = max(0.0, (float) $order->total - (float) $order->amount_paid);
        $note = isset($validated['reference']) ? 'Customer payment ref: '.$validated['reference'] : 'Customer online payment';

        $order = $action->execute($order, $remaining, $order->payment_method, $request->user(), $note);

        return new OrderResource($order->load(['customer', 'items', 'payments']));
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
