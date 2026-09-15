<?php

namespace App\Domain\ModelGeneration\Jobs;

use App\Domain\ModelGeneration\Contracts\ModelGenerator;
use App\Domain\ModelGeneration\Enums\GenerationStatus;
use App\Domain\ModelGeneration\Models\ModelGeneration;
use App\Domain\Models3D\Actions\UploadModelVersionAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Drives one image-to-3D generation: submit the photo, then re-queue itself to poll
 * every `poll_seconds` until the provider finishes. On success it downloads the GLB
 * (the provider URL expires) and records it as the product's current model version.
 */
class ProcessModelGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $generationId) {}

    public function handle(ModelGenerator $generator, UploadModelVersionAction $versions): void
    {
        $generation = ModelGeneration::find($this->generationId);
        if (! $generation || $generation->status->isTerminal()) {
            return;
        }

        if (! $generator->isConfigured()) {
            $this->fail($generation, '3D generation is not configured.');

            return;
        }

        $timeout = (int) config('model_generation.timeout_minutes', 15);
        if ($generation->created_at->diffInMinutes(now()) >= $timeout) {
            $this->fail($generation, 'Generation timed out. Please try again or upload a model manually.');

            return;
        }

        try {
            $generation->provider_task_id
                ? $this->pollAndMaybeFinalize($generation, $generator, $versions)
                : $this->submit($generation, $generator);
        } catch (Throwable $e) {
            // Transient (network/provider) error — retry until the timeout window closes.
            Log::warning('Model generation step failed; will retry.', ['generation' => $generation->id, 'error' => $e->getMessage()]);
            $this->requeue();
        }
    }

    private function submit(ModelGeneration $generation, ModelGenerator $generator): void
    {
        $taskId = $generator->submit($this->imageDataUri($generation->image_path));
        $generation->update(['provider_task_id' => $taskId, 'status' => GenerationStatus::PROCESSING]);
        $this->requeue();
    }

    private function pollAndMaybeFinalize(ModelGeneration $generation, ModelGenerator $generator, UploadModelVersionAction $versions): void
    {
        $result = $generator->poll((string) $generation->provider_task_id);
        $generation->update(['progress' => $result->progress]);

        if ($result->status === GenerationStatus::FAILED) {
            $this->fail($generation, $result->error ?? 'Generation failed.');

            return;
        }

        if ($result->status !== GenerationStatus::SUCCEEDED || ! $result->glbUrl) {
            $this->requeue();

            return;
        }

        $body = Http::timeout(60)->get($result->glbUrl)->throw()->body();
        $path = "models/products/{$generation->product_id}/generated-{$generation->id}.glb";
        Storage::put($path, $body);

        $version = $versions->record(
            $generation->product,
            $path,
            'glb',
            strlen($body),
            $generation->requested_by,
            'AI-generated from photo ('.$generation->provider.')',
        );

        $generation->update([
            'status' => GenerationStatus::SUCCEEDED,
            'progress' => 100,
            'model_version_id' => $version->id,
        ]);
    }

    private function requeue(): void
    {
        static::dispatch($this->generationId)->delay(now()->addSeconds((int) config('model_generation.poll_seconds', 15)));
    }

    private function fail(ModelGeneration $generation, string $message): void
    {
        $generation->update(['status' => GenerationStatus::FAILED, 'error' => $message]);
    }

    /** Provider wants the image as a base64 data URI. */
    private function imageDataUri(string $path): string
    {
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) Storage::get($path));
    }
}
