<?php

namespace App\Domain\Manufacturing\Events;

use App\Domain\Manufacturing\Models\ManufacturingStage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the shop-floor channel on every stage change. Pushes over Pusher
 * once BROADCAST_CONNECTION=pusher; until then it no-ops on the log driver and
 * the SPA falls back to polling.
 */
class ManufacturingStageUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public ManufacturingStage $stage) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('shop-floor')];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'stage_id' => $this->stage->id,
            'order_item_id' => $this->stage->order_item_id,
            'stage' => $this->stage->stage->value,
            'status' => $this->stage->status->value,
            'is_delayed' => $this->stage->is_delayed,
        ];
    }
}
