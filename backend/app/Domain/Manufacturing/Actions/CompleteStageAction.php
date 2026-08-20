<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Events\ManufacturingStageUpdated;
use App\Domain\Manufacturing\Models\ManufacturingStage;
use App\Models\User;

class CompleteStageAction
{
    /** Marks a stage done, computing delay against expected_minutes; records QC result for the QC stage. */
    public function execute(ManufacturingStage $stage, User $operator, ?bool $qcPassed = null, ?string $notes = null): ManufacturingStage
    {
        $ended = now();
        $delayed = $stage->started_at && $stage->expected_minutes
            ? $stage->started_at->diffInMinutes($ended) > $stage->expected_minutes
            : false;

        $stage->update([
            'status' => StageStatus::DONE,
            'operator_id' => $stage->operator_id ?? $operator->id,
            'started_at' => $stage->started_at ?? $ended,
            'ended_at' => $ended,
            'is_delayed' => $delayed,
            'qc_passed' => $stage->stage->isQc() ? ($qcPassed ?? true) : $stage->qc_passed,
            'notes' => $notes ?? $stage->notes,
        ]);

        event(new ManufacturingStageUpdated($stage));

        return $stage->refresh();
    }
}
