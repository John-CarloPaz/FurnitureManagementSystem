<?php

namespace App\Domain\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A colour variation of a product. The hex tints the shared 3D model so buyers can
 * preview each finish live.
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $color_hex
 * @property int $sort_order
 */
class ProductVariation extends Model
{
    protected $fillable = ['name', 'color_hex', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
