<?php

namespace App\Http\Resources;

use App\Domain\Orders\Models\DeliveryAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryAddress */
class DeliveryAddressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'country' => $this->country,
            'province_code' => $this->province_code,
            'province_name' => $this->province_name,
            'city_code' => $this->city_code,
            'city_name' => $this->city_name,
            'barangay_code' => $this->barangay_code,
            'barangay_name' => $this->barangay_name,
            'street' => $this->street,
            'landmark' => $this->landmark,
            'notes' => $this->notes,
            'is_default' => $this->is_default,
            'formatted' => $this->formatted(),
        ];
    }
}
