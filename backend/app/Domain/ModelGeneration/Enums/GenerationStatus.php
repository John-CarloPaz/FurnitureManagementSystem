<?php

namespace App\Domain\ModelGeneration\Enums;

enum GenerationStatus: string
{
    case PENDING = 'pending';       // queued, not yet submitted to the provider
    case PROCESSING = 'processing'; // provider is working on it
    case SUCCEEDED = 'succeeded';   // GLB downloaded + stored as a model version
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Queued',
            self::PROCESSING => 'Generating 3D model…',
            self::SUCCEEDED => 'Ready',
            self::FAILED => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::SUCCEEDED || $this === self::FAILED;
    }
}
