<?php

namespace App\Http\Controllers\Api;

use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductVariationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductVariationController extends Controller
{
    /**
     * Upsert a product's colour variations by id (authorized via ProductPolicy::update).
     * Existing rows keep their photo/3D model; rows left out are removed (assets cleaned up).
     */
    public function sync(Request $request, Product $product): AnonymousResourceCollection
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'variations' => ['present', 'array', 'max:12'],
            'variations.*.id' => ['nullable', 'integer'],
            'variations.*.name' => ['required', 'string', 'max:60'],
            'variations.*.color_hex' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        DB::transaction(function () use ($product, $data) {
            $keep = [];
            foreach (array_values($data['variations']) as $i => $input) {
                $attrs = ['name' => $input['name'], 'color_hex' => strtolower($input['color_hex']), 'sort_order' => $i];
                $existing = ! empty($input['id']) ? $product->variations()->whereKey($input['id'])->first() : null;

                if ($existing) {
                    $existing->update($attrs);
                    $keep[] = $existing->id;
                } else {
                    $keep[] = $product->variations()->create($attrs)->id;
                }
            }

            $product->variations()->whereNotIn('id', $keep ?: [0])->get()->each(function ($variation) {
                if ($variation->image_path) {
                    Storage::delete($variation->image_path);
                }
                if ($variation->model_path) {
                    Storage::delete($variation->model_path);
                }
                $variation->delete();
            });
        });

        return ProductVariationResource::collection($product->variations()->with('latestModelGeneration')->get());
    }
}
