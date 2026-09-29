<?php

namespace App\Http\Resources;

use App\Domain\Orders\Models\IssueReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IssueReport */
class IssueReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order->order_number),
            'category' => $this->category,
            'description' => $this->description,
            'status' => $this->status,
            'resolution_note' => $this->resolution_note,
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter->name),
            'is_mine' => $request->user()?->id === $this->user_id,
            'created_at' => $this->created_at,
        ];
    }
}
