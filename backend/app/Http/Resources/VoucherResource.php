<?php

namespace App\Http\Resources;

use App\Domain\Orders\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Voucher */
class VoucherResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'min_spend' => $this->min_spend,
            'max_discount' => $this->max_discount,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'is_active' => $this->is_active,
            'description' => $this->description,
        ];
    }
}
