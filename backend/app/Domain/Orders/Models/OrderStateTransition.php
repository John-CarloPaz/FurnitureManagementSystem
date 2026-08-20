<?php

namespace App\Domain\Orders\Models;

use App\Domain\Orders\Enums\OrderState;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property OrderState|null $from_state
 * @property OrderState $to_state
 * @property int|null $actor_id
 * @property string|null $note
 * @property Carbon|null $created_at
 */
class OrderStateTransition extends Model
{
    protected $fillable = [
        'order_id',
        'from_state',
        'to_state',
        'actor_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'from_state' => OrderState::class,
            'to_state' => OrderState::class,
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
