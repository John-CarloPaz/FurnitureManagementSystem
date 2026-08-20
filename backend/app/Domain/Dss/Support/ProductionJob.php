<?php

namespace App\Domain\Dss\Support;

use Illuminate\Support\Carbon;

/** One unit of production work to be sequenced by a SchedulingStrategy. */
class ProductionJob
{
    public ?int $sequence = null;

    public function __construct(
        public readonly int $orderItemId,
        public readonly string $orderNumber,
        public readonly string $productName,
        public readonly Carbon $dueAt,
        public readonly int $leadDays,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sequence' => $this->sequence,
            'order_item_id' => $this->orderItemId,
            'order_number' => $this->orderNumber,
            'product_name' => $this->productName,
            'due_at' => $this->dueAt->toDateString(),
            'lead_days' => $this->leadDays,
        ];
    }
}
