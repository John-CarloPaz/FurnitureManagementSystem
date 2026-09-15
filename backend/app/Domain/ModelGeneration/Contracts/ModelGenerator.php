<?php

namespace App\Domain\ModelGeneration\Contracts;

use App\Domain\ModelGeneration\GenerationResult;

/**
 * A pluggable image-to-3D provider. Swap the binding in ModelGenerationServiceProvider
 * to move from Meshy to Tripo/fal.ai without touching the job or controller (DIP).
 */
interface ModelGenerator
{
    public function name(): string;

    /** False when no API key is configured — the feature stays off and manual upload is used. */
    public function isConfigured(): bool;

    /** Submit an image (as a base64 data URI) and return the provider's task id. */
    public function submit(string $imageDataUri): string;

    /** Check a task; a SUCCEEDED result carries the GLB download URL. */
    public function poll(string $taskId): GenerationResult;
}
