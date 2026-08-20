<?php

namespace App\Domain\Notifications\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to production managers when a manufacturing stage runs over its expected time. */
class StageDelayedNotification extends Notification
{
    public function __construct(
        public int $orderItemId,
        public string $stage,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'stage_delayed',
            'order_item_id' => $this->orderItemId,
            'stage' => $this->stage,
            'message' => "Stage '{$this->stage}' is delayed on item #{$this->orderItemId}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Production delay')
            ->line("Stage '{$this->stage}' is running over its expected time on item #{$this->orderItemId}.");
    }
}
