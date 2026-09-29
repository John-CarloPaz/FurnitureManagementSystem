<?php

namespace App\Domain\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A customer's request to return/refund a delivered order.
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property string $reason
 * @property string|null $description
 * @property string $status
 * @property string|null $resolution_note
 * @property string|null $refund_amount
 * @property int|null $handled_by
 * @property Carbon|null $created_at
 */
class ReturnRequest extends Model
{
    public const REASONS = ['damaged', 'defective', 'wrong_item', 'not_as_described', 'changed_mind', 'other'];

    public const STATUSES = ['REQUESTED', 'APPROVED', 'REJECTED', 'REFUNDED'];

    protected $fillable = [
        'order_id', 'user_id', 'reason', 'description',
        'status', 'resolution_note', 'refund_amount', 'handled_by',
    ];

    protected function casts(): array
    {
        return ['refund_amount' => 'decimal:2'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
