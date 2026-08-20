<?php

namespace App\Domain\Notifications;

use App\Domain\Manufacturing\Events\ManufacturingStageUpdated;
use App\Domain\Notifications\Listeners\NotifyOnOrderPlaced;
use App\Domain\Notifications\Listeners\NotifyOnOrderTransition;
use App\Domain\Notifications\Listeners\NotifyOnStageDelay;
use App\Domain\Orders\Events\OrderPlaced;
use App\Domain\Orders\Events\OrderTransitioned;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires domain events → notifications (in-app database + email). The Notifications
 * module subscribes to other modules' events; those modules never call it directly.
 */
class NotificationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(OrderPlaced::class, NotifyOnOrderPlaced::class);
        Event::listen(OrderTransitioned::class, NotifyOnOrderTransition::class);
        Event::listen(ManufacturingStageUpdated::class, NotifyOnStageDelay::class);
    }
}
