<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A discount code applied at checkout.
 *
 * @property int $id
 * @property string $code
 * @property string $type
 * @property string $value
 * @property string|null $min_spend
 * @property string|null $max_discount
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int|null $usage_limit
 * @property int $used_count
 * @property bool $is_active
 * @property string|null $description
 */
class Voucher extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code', 'type', 'value', 'min_spend', 'max_discount',
        'starts_at', 'expires_at', 'usage_limit', 'used_count', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_spend' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'used_count' => 'integer',
        ];
    }

    /** The peso discount this voucher grants on a given subtotal, never exceeding it. */
    public function discountFor(float $subtotal): float
    {
        $raw = $this->type === self::TYPE_PERCENT
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount !== null) {
            $raw = min($raw, (float) $this->max_discount);
        }

        return round(min($raw, $subtotal), 2);
    }
}
