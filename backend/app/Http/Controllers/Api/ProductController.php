<?php

namespace App\Http\Controllers\Api;

use App\Domain\Products\Actions\ChangeProductStatusAction;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /** Catalog list. Staff (products.viewAny) see all; browsers see published only. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()->with(['images', 'model'])->latest();

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
    public function options(): JsonResponse
    {
        return response()->json(['data' => [
            'categories' => config('catalog.categories'),
            'materials' => config('catalog.materials'),
            'wood_types' => config('catalog.wood_types'),
            'finishes' => config('catalog.finishes'),
        ]]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = new Product($request->validated());
        $product->slug = $this->uniqueSlug((string) $request->input('name'));
        $product->created_by = $request->user()->id;
        $product->save();

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return new ProductResource($product->load(['images', 'model']));
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
