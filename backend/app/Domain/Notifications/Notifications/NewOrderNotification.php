<?php

namespace App\Domain\Notifications\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to admins when a new order is placed. */
class NewOrderNotification extends Notification
{
    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public string $customerName,
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
            'kind' => 'new_order',
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'message' => "New order {$this->orderNumber} from {$this->customerName}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New order {$this->orderNumber}")
            ->line("New order {$this->orderNumber} from {$this->customerName} needs confirmation.");
    }
}
