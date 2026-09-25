<?php

namespace App\Domain\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A customer's saved delivery address, reusable across repeat orders.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $label
 * @property string $country
 * @property string|null $province_code
 * @property string $province_name
 * @property string|null $city_code
 * @property string $city_name
 * @property string|null $barangay_code
 * @property string $barangay_name
 * @property string $street
 * @property string|null $landmark
 * @property string|null $notes
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DeliveryAddress extends Model
{
    protected $fillable = [
        'label', 'country',
        'province_code', 'province_name',
        'city_code', 'city_name',
        'barangay_code', 'barangay_name',
        'street', 'landmark', 'notes', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /** A single-line, human-readable rendering (street → country, with landmark). */
    public function formatted(): string
    {
        $parts = array_filter([
            $this->street,
            $this->barangay_name ? 'Brgy. '.$this->barangay_name : null,
            $this->city_name,
            $this->province_name,
            $this->country,
        ]);

        $line = implode(', ', $parts);

        if ($this->landmark) {
            $line .= " (Landmark: {$this->landmark})";
        }

        return $line;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
