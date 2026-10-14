<?php

namespace App\Domain\Products\Models;

use App\Domain\ModelGeneration\Models\ModelGeneration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A colour variation of a product. Each can carry its own photo and 3D model (uploaded
 * or generated); if it has none, the storefront tints the base model with the hex.
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $color_hex
 * @property string|null $image_path
 * @property string|null $model_path
 * @property string|null $model_format
 * @property int $sort_order
 */
class ProductVariation extends Model
{
    protected $fillable = ['name', 'color_hex', 'image_path', 'model_path', 'model_format', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasOne<ModelGeneration, $this> */
    public function latestModelGeneration(): HasOne
    {
        return $this->hasOne(ModelGeneration::class, 'variation_id')->latestOfMany();
    }
}
