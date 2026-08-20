<?php

/*
|--------------------------------------------------------------------------
| Catalog dropdown options
|--------------------------------------------------------------------------
| Single source of truth for product attribute dropdowns. Served to the SPA
| via GET /api/v1/products/options and used to validate product input.
| Edit these lists to change the available choices — no code changes needed.
*/

return [
    'categories' => [
        'Tables',
        'Chairs',
        'Sofas & Couches',
        'Beds',
        'Cabinets & Storage',
        'Shelves & Bookcases',
        'Desks',
        'Wardrobes',
        'Dining Sets',
        'Benches & Stools',
        'Outdoor',
    ],

    'materials' => [
        'Solid Wood',
        'Plywood',
        'MDF',
        'Rattan',
        'Metal',
        'Glass',
        'Upholstered / Fabric',
        'Leather',
        'Bamboo',
        'Particle Board',
    ],

    'wood_types' => [
        'Narra',
        'Acacia',
        'Mahogany',
        'Mango Wood',
        'Oak',
        'Walnut',
        'Teak',
        'Pine',
        'Gemelina',
        'Bamboo',
    ],

    'finishes' => [
        'Natural',
        'Matte',
        'Glossy',
        'Painted',
        'Stained',
        'Lacquered',
        'Varnished',
        'Distressed',
    ],
];
