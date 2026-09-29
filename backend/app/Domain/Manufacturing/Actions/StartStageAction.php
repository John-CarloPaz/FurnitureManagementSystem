<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Events\ManufacturingStageUpdated;
use App\Domain\Manufacturing\Models\ManufacturingStage;
use App\Domain\Manufacturing\Support\StageSequence;
use App\Models\User;

class StartStageAction
{
    public function execute(ManufacturingStage $stage, User $operator): ManufacturingStage
    {
        StageSequence::assertPreviousDone($stage);

        $stage->update([
            'status' => StageStatus::IN_PROGRESS,
            'operator_id' => $operator->id,
            'started_at' => $stage->started_at ?? now(),
        ]);

        event(new ManufacturingStageUpdated($stage));

        return $stage->refresh();
    }
}
