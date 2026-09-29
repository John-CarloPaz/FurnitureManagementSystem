<?php

namespace App\Domain\Manufacturing\Support;

use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Manufacturing\Models\ManufacturingStage;
use Illuminate\Validation\ValidationException;

/** Enforces the fixed shop-floor order — a stage can't be worked until the one before it is done. */
class StageSequence
{
    public static function assertPreviousDone(ManufacturingStage $stage): void
    {
        $ordered = StageType::ordered();
        $index = array_search($stage->stage, $ordered, true);

        if ($index === false || $index === 0) {
            return; // first stage (Cutting) has no prerequisite
        }

        $previousType = $ordered[$index - 1];
        $previous = ManufacturingStage::query()
            ->where('order_item_id', $stage->order_item_id)
            ->where('stage', $previousType->value)
            ->first();

        if ($previous === null || $previous->status !== StageStatus::DONE) {
            throw ValidationException::withMessages([
                'stage' => ["Complete {$previousType->label()} first before working on {$stage->stage->label()}."],
            ]);
        }
    }
}
