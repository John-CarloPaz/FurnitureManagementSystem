<?php

namespace App\Domain\ModelGeneration\Actions;

use App\Domain\ModelGeneration\Enums\GenerationStatus;
use App\Domain\ModelGeneration\Jobs\ProcessModelGeneration;
use App\Domain\ModelGeneration\Models\ModelGeneration;
use App\Domain\Products\Models\Product;
use App\Models\User;

/**
 * Queues an image-to-3D generation for an already-stored product photo. The heavy
 * provider work happens off-request in ProcessModelGeneration.
 */
class GenerateProductModelAction
{
    public function execute(Product $product, string $imagePath, ?User $requester): ModelGeneration
    {
        $generation = $product->modelGenerations()->create([
            'image_path' => $imagePath,
            'provider' => (string) config('model_generation.provider'),
            'status' => GenerationStatus::PENDING,
            'requested_by' => $requester?->id,
        ]);

        ProcessModelGeneration::dispatch($generation->id);

        return $generation;
    }
}
