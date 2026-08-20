<?php

namespace App\Domain\Delivery\Models;

use App\Domain\Delivery\Enums\DeliveryEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delivery_assignment_id
 * @property DeliveryEventType $type
 * @property string|null $lat
 * @property string|null $lng
 * @property string|null $manual_location
 * @property string|null $note
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
class DeliveryEvent extends Model
{
    protected $fillable = [
        'delivery_assignment_id', 'type', 'lat', 'lng', 'manual_location', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return ['type' => DeliveryEventType::class, 'lat' => 'decimal:7', 'lng' => 'decimal:7'];
    }

    /** @return BelongsTo<DeliveryAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class, 'delivery_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
