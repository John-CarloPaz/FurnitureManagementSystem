<?php

namespace App\Domain\Orders\Support;

use App\Domain\Settings\Support\Settings;

/** Turns a subtotal + discount into the full order pricing breakdown (shipping + VAT + total). */
class PricingCalculator
{
    /**
     * @return array{subtotal: float, discount: float, shipping: float, tax: float, total: float}
     */
    public static function compute(float $subtotal, float $discount = 0.0): array
    {
        $shipping = (float) Settings::get('shop.shipping_fee', config('shop.shipping_fee'));
        $taxable = max(0.0, $subtotal - $discount);
        $tax = round($taxable * (float) Settings::get('shop.vat_rate', config('shop.vat_rate')), 2);
        $total = round($taxable + $shipping + $tax, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shipping, 2),
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
