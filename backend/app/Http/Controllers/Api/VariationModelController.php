<?php

namespace App\Http\Controllers\Api;

use App\Domain\ModelGeneration\Contracts\ModelGenerator;
use App\Domain\ModelGeneration\Enums\GenerationStatus;
use App\Domain\ModelGeneration\Jobs\ProcessModelGeneration;
use App\Domain\ModelGeneration\Models\ModelGeneration;
use App\Domain\Products\Models\Product;
use App\Domain\Products\Models\ProductVariation;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductVariationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Per-variation 3D assets: upload a .glb, generate one from a photo, and stream both. */
class VariationModelController extends Controller
{
    /** Upload a .glb/.obj as this variation's own 3D model. */
    public function uploadModel(Request $request, Product $product, ProductVariation $variation): ProductVariationResource
    {
        $this->authorizeVariation($request, $product, $variation);

        $request->validate(['file' => ['required', 'file', 'max:51200']]); // 50 MB
        $file = $request->file('file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, ['glb', 'gltf', 'obj'], true)) {
            throw ValidationException::withMessages(['file' => ['Upload a .glb, .gltf or .obj 3D model.']]);
        }

        if ($variation->model_path) {
            Storage::delete($variation->model_path);
        }

        $path = $file->store("models/variations/{$variation->id}");
        $variation->update(['model_path' => $path, 'model_format' => $ext]);

        return new ProductVariationResource($variation->load('latestModelGeneration'));
    }

    /** Upload a photo for this variation and (if configured) generate its 3D model from it. */
    public function generate(Request $request, Product $product, ProductVariation $variation, ModelGenerator $generator): ProductVariationResource
    {
        $this->authorizeVariation($request, $product, $variation);

        $request->validate(['image' => ['required', 'image', 'max:10240']]); // 10 MB

        // The uploaded photo becomes the variation's display image.
        if ($variation->image_path) {
            Storage::delete($variation->image_path);
        }
        $imagePath = $request->file('image')->store("product-images/{$product->id}/variations/{$variation->id}");
        $variation->update(['image_path' => $imagePath]);

        if ($generator->isConfigured()) {
            $generation = ModelGeneration::create([
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'image_path' => $imagePath,
                'provider' => (string) config('model_generation.provider'),
                'status' => GenerationStatus::PENDING,
                'requested_by' => $request->user()->id,
            ]);

            ProcessModelGeneration::dispatch($generation->id);
        }

        return new ProductVariationResource($variation->load('latestModelGeneration'));
    }

    /** Stream the variation's 3D model — protected by the signed URL only. */
    public function file(ProductVariation $variation): StreamedResponse
    {
        abort_if($variation->model_path === null, 404);

        return Storage::download($variation->model_path, "variation-{$variation->id}.{$variation->model_format}");
    }

    /** Stream the variation's photo (public — marketplace display). */
    public function image(ProductVariation $variation): StreamedResponse
    {
        abort_if($variation->image_path === null, 404);

        return Storage::download($variation->image_path);
    }

    private function authorizeVariation(Request $request, Product $product, ProductVariation $variation): void
    {
        $this->authorize('update', $product);
        abort_unless($variation->product_id === $product->id, 404);
    }
}
