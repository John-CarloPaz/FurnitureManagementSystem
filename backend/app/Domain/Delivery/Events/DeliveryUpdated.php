<?php

namespace App\Domain\Delivery\Events;

use App\Domain\Delivery\Models\DeliveryAssignment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the deliveries channel on every delivery change. No-ops on the
 * log driver; pushes over Pusher once BROADCAST_CONNECTION=pusher (SPA polls until then).
 */
class DeliveryUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public DeliveryAssignment $assignment) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('deliveries')];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'order_id' => $this->assignment->order_id,
            'status' => $this->assignment->status->value,
        ];
    }
}
