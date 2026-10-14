<?php

namespace App\Http\Resources;

use App\Domain\Products\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin ProductVariation */
class ProductVariationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color_hex' => $this->color_hex,
            'has_model' => $this->model_path !== null,
            'model_format' => $this->model_format,
            'model_url' => $this->model_path !== null
                ? URL::temporarySignedRoute('variations.file', now()->addHour(), ['variation' => $this->id], absolute: false)
                : null,
            'image_url' => $this->image_path !== null
                ? URL::to("/api/v1/shop/variations/{$this->id}/image")
                : null,
            'generation' => $this->whenLoaded('latestModelGeneration', fn () => $this->latestModelGeneration ? [
                'status' => $this->latestModelGeneration->status->value,
                'progress' => $this->latestModelGeneration->progress,
                'error' => $this->latestModelGeneration->error,
            ] : null),
        ];
    }
}
