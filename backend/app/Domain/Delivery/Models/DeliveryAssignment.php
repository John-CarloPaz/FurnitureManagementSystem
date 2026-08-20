<?php

namespace App\Domain\Delivery\Models;

use App\Domain\Delivery\Enums\DeliveryStatus;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $coordinator_id
 * @property int|null $driver_id
 * @property string|null $batch_label
 * @property DeliveryStatus $status
 * @property Carbon|null $assigned_at
 */
class DeliveryAssignment extends Model
{
    protected $fillable = ['order_id', 'coordinator_id', 'driver_id', 'batch_label', 'status', 'assigned_at'];

    protected $attributes = ['status' => 'assigned'];

    protected function casts(): array
    {
        return ['status' => DeliveryStatus::class, 'assigned_at' => 'datetime'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    /** @return HasMany<DeliveryEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(DeliveryEvent::class)->latest();
    }

    /** @return HasOne<ProofOfDelivery, $this> */
    public function proof(): HasOne
    {
        return $this->hasOne(ProofOfDelivery::class);
    }
}
