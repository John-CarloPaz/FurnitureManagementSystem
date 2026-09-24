<?php

namespace App\Http\Resources;

use App\Domain\Products\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * Public marketplace shape for a published product (never exposes drafts).
 *
 * @mixin Product
 */
class StorefrontProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'material' => $this->material,
            'wood_type' => $this->wood_type,
            'finish' => $this->finish,
            'dimensions_label' => $this->dimensionsLabel(),
            'description' => $this->description,
            'base_price' => $this->base_price,
            'lead_time_days' => $this->lead_time_days,
            'image_url' => $primary ? URL::to("/api/v1/shop/product-images/{$primary->id}/file") : null,
            'has_model' => (bool) ($this->model && $this->model->current_version_id),
        ];
    }
}
