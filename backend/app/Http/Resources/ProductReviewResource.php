<?php

namespace App\Http\Resources;

use App\Domain\Products\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductReview */
class ProductReviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'reviewer' => $this->whenLoaded('user', fn () => $this->user->name),
            'is_mine' => $request->user()?->id === $this->user_id,
            'created_at' => $this->created_at,
        ];
    }
}
