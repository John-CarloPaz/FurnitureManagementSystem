<?php

namespace App\Domain\Notifications\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the customer whenever their order changes status. */
class OrderStatusNotification extends Notification
{
    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public string $status,
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
            'kind' => 'order_status',
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'status' => $this->status,
            'message' => self::message($this->status, $this->orderNumber),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order {$this->orderNumber} — ".ucwords(strtolower(str_replace('_', ' ', $this->status))))
            ->line(self::message($this->status, $this->orderNumber))
            ->line('Thank you for choosing Cedarside Holding Corp.');
    }

    public static function message(string $status, string $orderNumber): string
    {
        return match ($status) {
            'PLACED' => "Your order {$orderNumber} has been placed.",
            'CONFIRMED' => "Your order {$orderNumber} is confirmed and queued for production.",
            'IN_PRODUCTION' => "Good news — your order {$orderNumber} is now in production.",
            'QUALITY_CHECK' => "Your order {$orderNumber} is undergoing quality checks.",
            'REWORK' => "Your order {$orderNumber} is being reworked to meet our standards.",
            'READY_FOR_DELIVERY' => "Your order {$orderNumber} is ready and awaiting dispatch.",
            'OUT_FOR_DELIVERY' => "Your order {$orderNumber} is out for delivery.",
            'DELIVERED' => "Your order {$orderNumber} has been delivered. Enjoy!",
            'COMPLETED' => "Your order {$orderNumber} is complete. Thank you!",
            'CANCELLED' => "Your order {$orderNumber} has been cancelled.",
            default => "Your order {$orderNumber} was updated.",
        };
    }
}
