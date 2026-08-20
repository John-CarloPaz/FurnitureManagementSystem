<?php

namespace App\Domain\Notifications\Listeners;

use App\Domain\Manufacturing\Events\ManufacturingStageUpdated;
use App\Domain\Notifications\Notifications\StageDelayedNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/** Alert production managers when a manufacturing stage is flagged delayed. */
class NotifyOnStageDelay
{
    public function handle(ManufacturingStageUpdated $event): void
    {
        if (! $event->stage->is_delayed) {
            return;
        }

        Notification::send(
            User::role('production_manager')->get(),
            new StageDelayedNotification($event->stage->order_item_id, $event->stage->stage->value),
        );
    }
}
