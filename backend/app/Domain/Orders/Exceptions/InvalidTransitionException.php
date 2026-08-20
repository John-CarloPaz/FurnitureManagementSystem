<?php

namespace App\Domain\Orders\Exceptions;

use App\Domain\Orders\Enums\OrderState;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class InvalidTransitionException extends RuntimeException
{
    public function __construct(
        public readonly OrderState $from,
        public readonly OrderState $to,
        ?string $reason = null,
    ) {
        parent::__construct($reason ?? "Cannot transition order from {$from->label()} to {$to->label()}.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
