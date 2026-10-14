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

    /*
    | Goodwill discount (%) auto-issued to a customer when their order is flagged
    | delayed past its expected delivery date. Set to 0 to disable compensation.
    */
    'delay_goodwill_percent' => (float) env('SHOP_DELAY_GOODWILL_PERCENT', 10),

    /*
    | Standard number of days to deliver after an order finishes production — used by
    | the DSS to recommend a delivery date.
    */
    'delivery_window_days' => (int) env('SHOP_DELIVERY_WINDOW_DAYS', 3),
];
