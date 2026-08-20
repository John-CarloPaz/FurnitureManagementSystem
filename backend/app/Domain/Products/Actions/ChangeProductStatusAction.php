<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * Product catalog lifecycle. Publishing is guarded: a product needs a 3D model
 * and a price before customers can see it (docs/design/FSM.md §1).
 */
class ChangeProductStatusAction
{
    public function execute(Product $product, ProductStatus $to): Product
    {
        if (! $product->status->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot change product from {$product->status->label()} to {$to->label()}."],
            ]);
        }

        if ($to === ProductStatus::PUBLISHED) {
            $this->assertPublishable($product);
        }

        $product->update([
            'status' => $to,
            'published_at' => $to === ProductStatus::PUBLISHED ? now() : $product->published_at,
        ]);

        return $product->refresh();
    }

    private function assertPublishable(Product $product): void
    {
        $hasModel = $product->model && $product->model->versions()->exists();
        $hasPrice = (float) $product->base_price > 0;

        if (! $hasModel || ! $hasPrice) {
            throw ValidationException::withMessages([
                'status' => ['A product needs a 3D model and a price above 0 before it can be published.'],
            ]);
        }
    }
}
