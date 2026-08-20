<?php

return [
    /*
    | On-time delivery is measured against this target lead time (days from
    | order placement to delivery). Edit to match the shop's SLA.
    */
    'target_lead_days' => (int) env('ANALYTICS_TARGET_LEAD_DAYS', 14),
];
