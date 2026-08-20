<?php

namespace App\Http\Resources;

use App\Domain\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class ShopFloorOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $hasDelay = $this->items
            ->flatMap(fn ($item) => $item->stages)
            ->contains(fn ($stage) => $stage->is_delayed);

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'has_delay' => $hasDelay,
            'items' => ProductionItemResource::collection($this->items),
        ];
    }
}
