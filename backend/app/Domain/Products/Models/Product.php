<?php

namespace App\Domain\Products\Models;

use App\Domain\Models3D\Models\Model3D;
use App\Domain\Products\Enums\ProductStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $category
 * @property string|null $material
 * @property string|null $wood_type
 * @property string|null $finish
 * @property string|null $width_cm
 * @property string|null $depth_cm
 * @property string|null $height_cm
 * @property string|null $weight_kg
 * @property string $base_price
 * @property int|null $lead_time_days
 * @property ProductStatus $status
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'category', 'material', 'wood_type', 'finish',
        'width_cm', 'depth_cm', 'height_cm', 'weight_kg',
        'base_price', 'lead_time_days', 'status', 'published_at', 'created_by',
    ];

    /** Defaults so a new instance reflects the DB defaults before refresh. */
    protected $attributes = [
        'status' => 'DRAFT',
        'base_price' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'published_at' => 'datetime',
            'base_price' => 'decimal:2',
            'width_cm' => 'decimal:1',
            'depth_cm' => 'decimal:1',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:2',
        ];
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /** @return HasOne<Model3D, $this> */
    public function model(): HasOne
    {
        return $this->hasOne(Model3D::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
