<?php

namespace App\Domain\ModelGeneration\Models;

use App\Domain\ModelGeneration\Enums\GenerationStatus;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One attempt to turn an uploaded photo into a product's 3D model.
 *
 * @property int $id
 * @property int $product_id
 * @property string $image_path
 * @property string $provider
 * @property string|null $provider_task_id
 * @property GenerationStatus $status
 * @property int $progress
 * @property string|null $error
 * @property int|null $model_version_id
 * @property int|null $requested_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ModelGeneration extends Model
{
    protected $fillable = [
        'product_id', 'image_path', 'provider', 'provider_task_id',
        'status', 'progress', 'error', 'model_version_id', 'requested_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'progress' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
