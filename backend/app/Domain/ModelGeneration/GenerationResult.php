<?php

namespace App\Domain\ModelGeneration;

use App\Domain\ModelGeneration\Enums\GenerationStatus;

/** A provider's normalized answer when polling a generation task. */
final class GenerationResult
{
    public function __construct(
        public readonly GenerationStatus $status,
        public readonly int $progress = 0,
        public readonly ?string $glbUrl = null,
        public readonly ?string $error = null,
    ) {}
}
