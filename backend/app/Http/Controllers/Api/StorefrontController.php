<?php

namespace App\Http\Controllers\Api;

use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Domain\Products\Models\ProductImage;
use App\Http\Controllers\Controller;
use App\Http\Resources\StorefrontProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Public marketplace — browse published products (no auth). Ordering still needs a login. */
class StorefrontController extends Controller
{
    /** Published catalogue with optional ?category, ?q search and ?sort ordering. */
    public function products(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()
            ->where('status', ProductStatus::PUBLISHED->value)
            ->with(['images', 'model']);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($q = $request->query('q')) {
            $query->where('name', 'like', '%'.$q.'%');
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'name' => $query->orderBy('name'),
            default => $query->latest('published_at'),
        };

        return StorefrontProductResource::collection(
            $query->paginate(12)->withQueryString()
        );
    }

    /** Distinct categories among published products, for the storefront filter tabs. */
    public function categories(): JsonResponse
    {
        $categories = Product::query()
            ->where('status', ProductStatus::PUBLISHED->value)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json(['data' => $categories]);
    }

    /** A single published product with a temporary signed URL to its 3D model. */
    public function product(Product $product): JsonResponse
    {
        abort_unless($product->status === ProductStatus::PUBLISHED, 404);

        $product->load(['images', 'model.currentVersion']);
        $version = $product->model?->currentVersion;

        $data = (new StorefrontProductResource($product))->resolve();
        $data['model_url'] = $version
            ? URL::temporarySignedRoute('model-versions.file', now()->addHour(), ['version' => $version->id], absolute: false)
            : null;
        $data['model_format'] = $version?->format;

        return response()->json(['data' => $data]);
    }

    /** Stream a product photo (public — marketplace thumbnails). */
    public function image(ProductImage $image): StreamedResponse
    {
        return Storage::download($image->path);
    }
}
