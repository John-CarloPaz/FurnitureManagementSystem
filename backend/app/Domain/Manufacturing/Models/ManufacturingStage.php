<?php

namespace App\Domain\Manufacturing\Models;

use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Orders\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_item_id
 * @property StageType $stage
 * @property StageStatus $status
 * @property int|null $operator_id
 * @property int|null $expected_minutes
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property bool $is_delayed
 * @property bool|null $qc_passed
 * @property string|null $notes
 * @property Carbon|null $created_at
 */
class ManufacturingStage extends Model
{
    protected $fillable = [
        'order_item_id', 'stage', 'status', 'operator_id', 'expected_minutes',
        'started_at', 'ended_at', 'is_delayed', 'qc_passed', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'stage' => StageType::class,
            'status' => StageStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_delayed' => 'boolean',
            'qc_passed' => 'boolean',
        ];
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
