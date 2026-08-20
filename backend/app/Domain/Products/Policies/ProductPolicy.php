<?php

namespace App\Domain\Products\Policies;

use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['products.viewAny', 'products.browse']);
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->can('products.viewAny')) {
            return true;
        }

        // Browsers only see published products.
        return $user->can('products.browse') && $product->status === ProductStatus::PUBLISHED;
    }

    public function create(User $user): bool
    {
        return $user->can('products.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.manage');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.manage');
    }

    public function publish(User $user, Product $product): bool
    {
        return $user->can('products.publish');
    }
}
