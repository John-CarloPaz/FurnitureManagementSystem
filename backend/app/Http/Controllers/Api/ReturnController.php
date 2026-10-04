<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Actions\SendReturnStatusEmail;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\ReturnRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReturnRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReturnController extends Controller
{
    /** Staff (returns.viewAny) see everything; customers see only their own. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ReturnRequest::query()->with(['order', 'requester'])->latest();

        if (! $request->user()?->can('returns.viewAny')) {
            $query->where('user_id', $request->user()?->id);
        }

        return ReturnRequestResource::collection($query->get());
    }

    /** Customer opens a return/refund request against their own delivered order. */
    public function store(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($order->customer_id === $user?->id, 403);

        if (! in_array($order->status, [OrderState::DELIVERED, OrderState::COMPLETED], true)) {
            throw ValidationException::withMessages(['order' => ['You can only request a return after the order is delivered.']]);
        }
        if ($order->returnRequests()->whereIn('status', ['REQUESTED', 'APPROVED', 'REFUNDED'])->exists()) {
            throw ValidationException::withMessages(['order' => ['There is already an active return request for this order.']]);
        }

        $validated = $request->validate([
            'reason' => ['required', Rule::in(ReturnRequest::REASONS)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = $order->returnRequests()->create([
            'user_id' => $user->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'REQUESTED',
        ]);

        return (new ReturnRequestResource($return->load(['order', 'requester'])))->response()->setStatusCode(201);
    }

    /** Staff (returns.manage) approve / reject / refund a request. */
    public function update(Request $request, ReturnRequest $returnRequest, SendReturnStatusEmail $mailer): ReturnRequestResource
    {
        abort_unless($request->user()?->can('returns.manage'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['APPROVED', 'REJECTED', 'REFUNDED'])],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $previousStatus = $returnRequest->status;

        $returnRequest->update([
            'status' => $validated['status'],
            'resolution_note' => $validated['resolution_note'] ?? $returnRequest->resolution_note,
            'refund_amount' => $validated['status'] === 'REFUNDED'
                ? ($validated['refund_amount'] ?? $returnRequest->order->total)
                : $returnRequest->refund_amount,
            'handled_by' => $request->user()->id,
        ]);

        $returnRequest->load(['order', 'requester']);

        if ($validated['status'] !== $previousStatus) {
            $mailer->execute($returnRequest);
        }

        return new ReturnRequestResource($returnRequest);
    }
}
