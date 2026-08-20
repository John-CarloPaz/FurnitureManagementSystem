<?php

use App\Domain\Delivery\DeliveryServiceProvider;
use App\Domain\Dss\DssServiceProvider;
use App\Domain\Manufacturing\ManufacturingServiceProvider;
use App\Domain\Notifications\NotificationsServiceProvider;
use App\Domain\Orders\OrdersServiceProvider;
use App\Domain\Products\ProductsServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    OrdersServiceProvider::class,
    ProductsServiceProvider::class,
    ManufacturingServiceProvider::class,
    DeliveryServiceProvider::class,
    NotificationsServiceProvider::class,
    DssServiceProvider::class,
];
