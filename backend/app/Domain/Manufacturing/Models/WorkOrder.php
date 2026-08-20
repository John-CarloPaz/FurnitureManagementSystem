<?php

namespace App\Domain\Manufacturing\Models;

use App\Domain\Orders\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int|null $schedule_id
 * @property int|null $assigned_to
 * @property int|null $sequence
 * @property Carbon|null $scheduled_start
 * @property Carbon|null $scheduled_end
 * @property string $status
 */
class WorkOrder extends Model
{
    protected $fillable = [
        'order_item_id', 'schedule_id', 'assigned_to', 'sequence',
        'scheduled_start', 'scheduled_end', 'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
        ];
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
