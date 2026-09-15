<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Stores an uploaded photo as the listing's new primary image and returns its path. */
class StoreProductImageAction
{
    public function execute(Product $product, UploadedFile $image): string
    {
        $path = $image->store("product-images/{$product->id}");

        DB::transaction(function () use ($product, $path) {
            $product->images()->update(['is_primary' => false]);
            $product->images()->create([
                'path' => $path,
                'sort_order' => (int) $product->images()->max('sort_order') + 1,
                'is_primary' => true,
            ]);
        });

        return $path;
    }
}
