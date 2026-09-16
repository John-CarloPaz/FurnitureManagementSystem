<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Manufacturing\Enums\StageStatus;
use App\Domain\Manufacturing\Enums\StageType;
use App\Domain\Manufacturing\Events\ManufacturingStageUpdated;
use App\Domain\Manufacturing\Models\QualityInspection;
use App\Domain\Orders\Actions\TransitionOrderAction;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Records a QC verdict on an item. A pass closes the QC stage; a fail captures the
 * reason + defect photos, resets the item's stages to PENDING (so it repeats the whole
 * process — the progress bar resets), and sends the order back to REWORK.
 */
class RecordQualityInspectionAction
{
    public function __construct(private readonly TransitionOrderAction $transition) {}

    /** @param  array<int, UploadedFile>  $photos */
    public function execute(OrderItem $item, User $inspector, bool $passed, ?string $reason = null, array $photos = []): QualityInspection
    {
        return DB::transaction(function () use ($item, $inspector, $passed, $reason, $photos) {
            $inspection = $item->qualityInspections()->create([
                'inspector_id' => $inspector->id,
                'passed' => $passed,
                'reason' => $passed ? null : $reason,
                'attempt' => (int) $item->qualityInspections()->max('attempt') + 1,
            ]);

            foreach ($photos as $photo) {
                $inspection->photos()->create(['path' => $photo->store("quality-inspections/{$item->id}")]);
            }

            $qcStage = $item->stages()->where('stage', StageType::QC->value)->first();

            if ($passed) {
                $qcStage?->update([
                    'status' => StageStatus::DONE,
                    'qc_passed' => true,
                    'operator_id' => $inspector->id,
                    'started_at' => $qcStage->started_at ?? now(),
                    'ended_at' => now(),
                ]);
            } else {
                // Repeat the whole process: every stage back to PENDING → progress resets to 0%.
                $item->stages()->update([
                    'status' => StageStatus::PENDING->value,
                    'operator_id' => null,
                    'started_at' => null,
                    'ended_at' => null,
                    'is_delayed' => false,
                    'qc_passed' => null,
                    'notes' => null,
                ]);

                // Move the order to REWORK (a QA-owned transition) so production can resume.
                $order = $item->order;
                if ($order->status === OrderState::QUALITY_CHECK) {
                    $this->transition->execute($order, OrderState::REWORK, $inspector, "QC failed: {$reason}");
                }
            }

            if ($qcStage) {
                event(new ManufacturingStageUpdated($qcStage->refresh()));
            }

            return $inspection->load(['photos', 'inspector']);
        });
    }
}
