<?php

namespace App\Http\Resources;

use App\Domain\Products\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'material' => $this->material,
            'wood_type' => $this->wood_type,
            'finish' => $this->finish,
            'width_cm' => $this->width_cm,
            'depth_cm' => $this->depth_cm,
            'height_cm' => $this->height_cm,
            'weight_kg' => $this->weight_kg,
            'dimensions_label' => $this->dimensionsLabel(),
            'base_price' => $this->base_price,
            'lead_time_days' => $this->lead_time_days,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'allowed_status_transitions' => array_map(
                fn ($s) => $s->value,
                $this->status->allowedTransitions(),
            ),
            'published_at' => $this->published_at,
            'model' => $this->whenLoaded('model', fn () => $this->model ? [
                'id' => $this->model->id,
                'current_version_id' => $this->model->current_version_id,
            ] : null),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'path' => $img->path,
                'is_primary' => $img->is_primary,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /** e.g. "W120 × D80 × H75 cm" from the present dimensions. */
    private function dimensionsLabel(): ?string
    {
        $parts = [];
        foreach (['W' => $this->width_cm, 'D' => $this->depth_cm, 'H' => $this->height_cm] as $prefix => $value) {
            if ($value !== null) {
                $parts[] = $prefix.rtrim(rtrim((string) $value, '0'), '.');
            }
        }

        return $parts ? implode(' × ', $parts).' cm' : null;
    }
}
