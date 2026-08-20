<?php

namespace App\Domain\Delivery\Actions;

use App\Domain\Delivery\Enums\DeliveryEventType;
use App\Domain\Delivery\Enums\DeliveryStatus;
use App\Domain\Delivery\Events\DeliveryUpdated;
use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Delivery\Models\ProofOfDelivery;
use App\Domain\Orders\Actions\TransitionOrderAction;
use App\Domain\Orders\Enums\OrderState;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Driver captures proof of delivery: photo + recipient → order DELIVERED. */
class RecordProofOfDeliveryAction
{
    public function __construct(private readonly TransitionOrderAction $transition) {}

    public function execute(DeliveryAssignment $assignment, User $driver, UploadedFile $photo, ?string $recipientName = null): ProofOfDelivery
    {
        return DB::transaction(function () use ($assignment, $driver, $photo, $recipientName) {
            $proof = $assignment->proof()->updateOrCreate([], [
                'photo_path' => $photo->store("deliveries/{$assignment->id}"),
                'recipient_name' => $recipientName,
                'delivered_at' => now(),
            ]);

            $assignment->events()->create([
                'type' => DeliveryEventType::DELIVERED,
                'created_by' => $driver->id,
            ]);

            $assignment->update(['status' => DeliveryStatus::DELIVERED]);

            $this->transition->execute(
                $assignment->order,
                OrderState::DELIVERED,
                $driver,
                'Delivered with proof',
            );

            event(new DeliveryUpdated($assignment));

            return $proof->refresh();
        });
    }
}
