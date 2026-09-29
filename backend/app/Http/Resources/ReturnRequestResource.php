<?php

namespace App\Http\Resources;

use App\Domain\Orders\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReturnRequest */
class ReturnRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order->order_number),
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'resolution_note' => $this->resolution_note,
            'refund_amount' => $this->refund_amount,
            'requester' => $this->whenLoaded('requester', fn () => $this->requester->name),
            'is_mine' => $request->user()?->id === $this->user_id,
            'created_at' => $this->created_at,
        ];
    }
}
