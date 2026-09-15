<?php

namespace App\Http\Controllers\Api;

use App\Domain\ModelGeneration\Actions\GenerateProductModelAction;
use App\Domain\ModelGeneration\Contracts\ModelGenerator;
use App\Domain\Products\Actions\ChangeProductStatusAction;
use App\Domain\Products\Actions\StoreProductImageAction;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\GenerateModelRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /** Catalog list. Staff (products.viewAny) see all; browsers see published only. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()->with(['images', 'model', 'latestModelGeneration'])->latest();

        if (! $request->user()?->can('products.viewAny')) {
            $query->where('status', ProductStatus::PUBLISHED->value);
        } elseif ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return ProductResource::collection($query->paginate(20));
    }

    /** Dropdown options for the product form (categories, materials, etc.). */
    public function options(ModelGenerator $generator): JsonResponse
    {
        return response()->json(['data' => [
            'categories' => config('catalog.categories'),
            'materials' => config('catalog.materials'),
            'wood_types' => config('catalog.wood_types'),
            'finishes' => config('catalog.finishes'),
            // Whether uploading a photo will auto-generate the 3D model.
            'model_generation_enabled' => $generator->isConfigured(),
        ]]);
    }

    public function store(StoreProductRequest $request, StoreProductImageAction $storeImage, GenerateProductModelAction $generate, ModelGenerator $generator): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = new Product($request->safe()->except('image'));
        $product->slug = $this->uniqueSlug((string) $request->input('name'));
        $product->created_by = $request->user()->id;
        $product->save();

        // The photo becomes the listing image; when a provider is configured it also
        // kicks off 3D generation. Otherwise the admin uploads a .glb manually later.
        if ($request->hasFile('image')) {
            $path = $storeImage->execute($product, $request->file('image'));
            if ($generator->isConfigured()) {
                $generate->execute($product, $path, $request->user());
            }
        }

        return (new ProductResource($product->load(['images', 'model', 'latestModelGeneration'])))
            ->response()->setStatusCode(201);
    }

    /** Re-run 3D generation for a product — from a new photo, or the last one used. */
    public function generateModel(GenerateModelRequest $request, Product $product, StoreProductImageAction $storeImage, GenerateProductModelAction $generate, ModelGenerator $generator): ProductResource
    {
        $this->authorize('update', $product);

        if (! $generator->isConfigured()) {
            throw ValidationException::withMessages(['image' => ['3D generation is not configured. Upload a .glb model instead.']]);
        }

        $path = $request->hasFile('image')
            ? $storeImage->execute($product, $request->file('image'))
            : $product->latestModelGeneration?->image_path;

        if (! $path) {
            throw ValidationException::withMessages(['image' => ['Upload a photo to generate a 3D model from.']]);
        }

        $generate->execute($product, $path, $request->user());

        return new ProductResource($product->load(['images', 'model', 'latestModelGeneration']));
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return new ProductResource($product->load(['images', 'model', 'latestModelGeneration']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return new ProductResource($product->load(['images', 'model']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['data' => ['message' => 'Product deleted.']]);
    }

    public function publish(Product $product, ChangeProductStatusAction $action): ProductResource
    {
        $this->authorize('publish', $product);

        return new ProductResource($action->execute($product, ProductStatus::PUBLISHED)->load(['images', 'model']));
    }

    public function unpublish(Product $product, ChangeProductStatusAction $action): ProductResource
    {
        $this->authorize('publish', $product);

        return new ProductResource($action->execute($product, ProductStatus::DRAFT)->load(['images', 'model']));
    }

    public function archive(Product $product, ChangeProductStatusAction $action): ProductResource
    {
        $this->authorize('publish', $product);

        return new ProductResource($action->execute($product, ProductStatus::ARCHIVED)->load(['images', 'model']));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}
