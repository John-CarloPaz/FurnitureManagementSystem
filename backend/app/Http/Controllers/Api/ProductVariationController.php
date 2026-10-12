<?php

namespace App\Http\Controllers\Api;

use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductVariationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductVariationController extends Controller
{
    /** Replace a product's colour variations (authorized via ProductPolicy::update). */
    public function sync(Request $request, Product $product): AnonymousResourceCollection
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'variations' => ['present', 'array', 'max:12'],
            'variations.*.name' => ['required', 'string', 'max:60'],
            'variations.*.color_hex' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        DB::transaction(function () use ($product, $data) {
            $product->variations()->delete();
            foreach (array_values($data['variations']) as $i => $variation) {
                $product->variations()->create([
                    'name' => $variation['name'],
                    'color_hex' => strtolower($variation['color_hex']),
                    'sort_order' => $i,
                ]);
            }
        });

        return ProductVariationResource::collection($product->variations()->get());
    }
}
