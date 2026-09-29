<?php

namespace App\Domain\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A customer's reported problem with a delivered order.
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property string $category
 * @property string $description
 * @property string $status
 * @property string|null $resolution_note
 * @property int|null $handled_by
 * @property Carbon|null $created_at
 */
class IssueReport extends Model
{
    public const CATEGORIES = ['delivery_problem', 'damaged_item', 'missing_item', 'wrong_item', 'product_quality', 'other'];

    public const STATUSES = ['OPEN', 'IN_REVIEW', 'RESOLVED'];

    protected $fillable = [
        'order_id', 'user_id', 'category', 'description',
        'status', 'resolution_note', 'handled_by',
    ];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
