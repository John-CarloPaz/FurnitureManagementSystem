<?php

namespace App\Domain\Manufacturing\Models;

use App\Domain\Orders\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One QC verdict on an ordered item. A fail carries the reason + defect photos and
 * sends the item back through production (its stages reset). `attempt` counts the
 * rework cycles so the history reads 1st QC, 2nd QC, …
 *
 * @property int $id
 * @property int $order_item_id
 * @property int|null $inspector_id
 * @property bool $passed
 * @property string|null $reason
 * @property int $attempt
 * @property Carbon $created_at
 */
class QualityInspection extends Model
{
    protected $fillable = ['order_item_id', 'inspector_id', 'passed', 'reason', 'attempt'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['passed' => 'boolean', 'attempt' => 'integer'];
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /** @return HasMany<QualityInspectionPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(QualityInspectionPhoto::class);
    }
}
