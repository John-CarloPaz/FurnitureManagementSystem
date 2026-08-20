<?php

namespace App\Domain\Products\Enums;

/** Catalog lifecycle. See docs/design/FSM.md §1. */
enum ProductStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }

    /** @return array<int, ProductStatus> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::ARCHIVED],
            self::PUBLISHED => [self::DRAFT, self::ARCHIVED],
            self::ARCHIVED => [self::DRAFT],
        };
    }

    public function canTransitionTo(ProductStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
