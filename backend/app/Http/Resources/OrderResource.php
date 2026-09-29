<?php

namespace App\Http\Resources;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\OrderStateFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'allowed_transitions' => array_map(
                fn ($s) => $s->value,
                OrderStateFactory::for($this->status)->allowedTransitions(),
            ),
            'customer_id' => $this->customer_id,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'voucher_code' => $this->voucher_code,
            'total' => $this->total,
            'downpayment' => $this->downpayment,
            'amount_paid' => $this->amount_paid,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => OrderPaymentResource::collection($this->whenLoaded('payments')),
            'transitions' => OrderTransitionResource::collection($this->whenLoaded('transitions')),
            'delivery' => $this->whenLoaded('deliveryAssignment', fn () => $this->deliveryAssignment
                ? new DeliveryAssignmentResource($this->deliveryAssignment)
                : null),
            'returns' => ReturnRequestResource::collection($this->whenLoaded('returnRequests')),
            'issues' => IssueReportResource::collection($this->whenLoaded('issueReports')),
            'placed_at' => $this->placed_at,
            'confirmed_at' => $this->confirmed_at,
            'delivered_at' => $this->delivered_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
