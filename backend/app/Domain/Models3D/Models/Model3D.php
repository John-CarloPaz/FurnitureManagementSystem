<?php

namespace App\Domain\Models3D\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $current_version_id
 */
class Model3D extends Model
{
    protected $table = 'models_3d';

    protected $fillable = ['product_id', 'current_version_id'];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<Model3DVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(Model3DVersion::class, 'model_id')->orderByDesc('version');
    }

    /** @return BelongsTo<Model3DVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(Model3DVersion::class, 'current_version_id');
    }
}
