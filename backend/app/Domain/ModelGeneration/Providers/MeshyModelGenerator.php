<?php

namespace App\Domain\ModelGeneration\Providers;

use App\Domain\ModelGeneration\Contracts\ModelGenerator;
use App\Domain\ModelGeneration\Enums\GenerationStatus;
use App\Domain\ModelGeneration\GenerationResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Meshy image-to-3D (https://docs.meshy.ai/en/api/image-to-3d). Submit returns a task
 * id; poll until SUCCEEDED, then the caller downloads model_urls.glb (which expires).
 */
class MeshyModelGenerator implements ModelGenerator
{
    public function name(): string
    {
        return 'meshy';
    }

    public function isConfigured(): bool
    {
        return (bool) config('model_generation.meshy.key');
    }

    public function submit(string $imageDataUri): string
    {
        $response = $this->client()->post($this->url('/image-to-3d'), [
            'image_url' => $imageDataUri,
            'ai_model' => config('model_generation.meshy.ai_model'),
            'topology' => config('model_generation.meshy.topology'),
            // Remesh so target_polycount is honoured — otherwise the mesh is huge.
            'should_remesh' => (bool) config('model_generation.meshy.should_remesh'),
            'target_polycount' => (int) config('model_generation.meshy.target_polycount'),
            'should_texture' => (bool) config('model_generation.meshy.should_texture'),
            'texture_resolution' => config('model_generation.meshy.texture_resolution'),
            'target_formats' => ['glb'],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Meshy submit failed: '.$response->status().' '.$response->body());
        }

        $taskId = $response->json('result');
        if (! is_string($taskId) || $taskId === '') {
            throw new RuntimeException('Meshy submit returned no task id.');
        }

        return $taskId;
    }

    public function poll(string $taskId): GenerationResult
    {
        $response = $this->client()->get($this->url("/image-to-3d/{$taskId}"));

        if ($response->failed()) {
            throw new RuntimeException('Meshy poll failed: '.$response->status().' '.$response->body());
        }

        $progress = (int) $response->json('progress', 0);
        $glb = $response->json('model_urls.glb');
        $error = $response->json('task_error.message');

        return match ($response->json('status')) {
            'SUCCEEDED' => new GenerationResult(
                GenerationStatus::SUCCEEDED,
                100,
                glbUrl: is_string($glb) ? $glb : null,
            ),
            'FAILED', 'CANCELED' => new GenerationResult(
                GenerationStatus::FAILED,
                $progress,
                error: is_string($error) && $error !== '' ? $error : 'The provider could not generate a model from this image.',
            ),
            default => new GenerationResult(GenerationStatus::PROCESSING, $progress),
        };
    }

    private function client(): PendingRequest
    {
        return Http::withToken((string) config('model_generation.meshy.key'))
            ->acceptJson()
            ->timeout(30);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('model_generation.meshy.endpoint'), '/').$path;
    }
}
