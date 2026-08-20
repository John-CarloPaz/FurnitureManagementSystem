<?php

namespace App\Domain\Manufacturing\Enums;

enum StageStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
