<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Models\Voucher;

/** Validates a voucher code against a subtotal and returns the discount, or a reason it can't apply. */
class ResolveVoucher
{
    /**
     * @return array{voucher: ?Voucher, discount: float, error: ?string}
     */
    public function execute(?string $code, float $subtotal): array
    {
        $code = $code !== null ? trim($code) : '';
        if ($code === '') {
            return ['voucher' => null, 'discount' => 0.0, 'error' => null];
        }

        $voucher = Voucher::query()->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

        if ($voucher === null) {
            return $this->fail('That voucher code is not valid.');
        }
        if (! $voucher->is_active) {
            return $this->fail('This voucher is no longer active.');
        }
        if ($voucher->starts_at !== null && now()->lt($voucher->starts_at)) {
            return $this->fail('This voucher is not active yet.');
        }
        if ($voucher->expires_at !== null && now()->gt($voucher->expires_at)) {
            return $this->fail('This voucher has expired.');
        }
        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            return $this->fail('This voucher has reached its usage limit.');
        }
        if ($voucher->min_spend !== null && $subtotal < (float) $voucher->min_spend) {
            return $this->fail('Spend at least ₱'.number_format((float) $voucher->min_spend, 2).' to use this voucher.');
        }

        return ['voucher' => $voucher, 'discount' => $voucher->discountFor($subtotal), 'error' => null];
    }

    /** @return array{voucher: null, discount: float, error: string} */
    private function fail(string $error): array
    {
        return ['voucher' => null, 'discount' => 0.0, 'error' => $error];
    }
}
