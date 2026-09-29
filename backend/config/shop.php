<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storefront pricing
    |--------------------------------------------------------------------------
    | Flat shipping fee added to every order, and the VAT rate applied to the
    | (discounted) subtotal. Both are overridable via env for demos/testing.
    */
    'shipping_fee' => (float) env('SHOP_SHIPPING_FEE', 500),

    'vat_rate' => (float) env('SHOP_VAT_RATE', 0.12),
];
