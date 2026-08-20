<?php

namespace App\Http\Resources;

use App\Domain\Manufacturing\Support\ProductionProgress;
use App\Domain\Orders\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class ProductionItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'percent' => ProductionProgress::itemPercent($this->resource),
            'stages' => ManufacturingStageResource::collection($this->whenLoaded('stages')),
        ];
    }
}
