<?php

namespace App\Http\Resources;

use App\Domain\Manufacturing\Models\ManufacturingStage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ManufacturingStage */
class ManufacturingStageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stage' => $this->stage->value,
            'stage_label' => $this->stage->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'operator_id' => $this->operator_id,
            'expected_minutes' => $this->expected_minutes,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'is_delayed' => $this->is_delayed,
            'qc_passed' => $this->qc_passed,
            'notes' => $this->notes,
        ];
    }
}
