<?php

namespace App\Http\Resources;

use App\Domain\Orders\Models\OrderStateTransition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderStateTransition */
class OrderTransitionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_state' => $this->from_state?->value,
            'to_state' => $this->to_state->value,
            'to_label' => $this->to_state->label(),
            'note' => $this->note,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'created_at' => $this->created_at,
        ];
    }
}
