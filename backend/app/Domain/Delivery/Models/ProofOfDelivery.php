<?php

namespace App\Domain\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delivery_assignment_id
 * @property string $photo_path
 * @property string|null $signature_path
 * @property string|null $recipient_name
 * @property Carbon $delivered_at
 */
class ProofOfDelivery extends Model
{
    protected $table = 'proof_of_deliveries';

    protected $fillable = [
        'delivery_assignment_id', 'photo_path', 'signature_path', 'recipient_name', 'delivered_at',
    ];

    protected function casts(): array
    {
        return ['recipient_name' => 'encrypted', 'delivered_at' => 'datetime'];
    }

    /** @return BelongsTo<DeliveryAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class, 'delivery_assignment_id');
    }
}
