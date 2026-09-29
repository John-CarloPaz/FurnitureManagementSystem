<?php

namespace App\Http\Controllers\Api;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Domain\Products\Models\Product;
use App\Domain\Products\Models\ProductReview;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /** Public: reviews for a product, newest first. */
    public function index(Product $product): AnonymousResourceCollection
    {
        return ProductReviewResource::collection(
            $product->reviews()->with('user')->latest()->get(),
        );
    }

    /** Customer: leave (or update) a review for a product they've received. */
    public function store(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $eligible = Order::query()
            ->where('customer_id', $user->id)
            ->whereIn('status', [OrderState::DELIVERED->value, OrderState::COMPLETED->value])
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        abort_unless($eligible, 403, 'You can review this product once your order has been delivered.');

        $review = ProductReview::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            ['rating' => $validated['rating'], 'comment' => $validated['comment'] ?? null],
        );

        return (new ProductReviewResource($review->load('user')))->response()->setStatusCode(201);
    }

    /** The review's author, or a moderator, may delete it. */
    public function destroy(Request $request, ProductReview $review): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->id === $review->user_id || (bool) $user?->can('reviews.moderate'), 403);

        $review->delete();

        return response()->json(status: 204);
    }
}
