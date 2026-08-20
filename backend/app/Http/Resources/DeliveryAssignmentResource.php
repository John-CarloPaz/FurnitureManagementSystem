<?php

namespace App\Http\Resources;

use App\Domain\Delivery\Models\DeliveryAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin DeliveryAssignment */
class DeliveryAssignmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status->value,
                'customer' => $this->order->customer?->name,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver?->name),
            'driver_id' => $this->driver_id,
            'coordinator' => $this->whenLoaded('coordinator', fn () => $this->coordinator?->name),
            'batch_label' => $this->batch_label,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assigned_at' => $this->assigned_at,
            'events' => DeliveryEventResource::collection($this->whenLoaded('events')),
            'proof' => $this->whenLoaded('proof', fn () => $this->proof ? [
                'id' => $this->proof->id,
                'recipient_name' => $this->proof->recipient_name,
                'delivered_at' => $this->proof->delivered_at,
                'photo_url' => URL::temporarySignedRoute(
                    'delivery-proofs.file',
                    now()->addHour(),
                    ['proof' => $this->proof->id],
                    absolute: false,
                ),
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
