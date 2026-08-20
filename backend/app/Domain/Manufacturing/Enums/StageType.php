<?php

namespace App\Domain\Manufacturing\Enums;

/** The shop-floor stages each ordered item passes through (caps §Manufacturing Tracking). */
enum StageType: string
{
    case CUTTING = 'cutting';
    case ASSEMBLY = 'assembly';
    case SANDING = 'sanding';
    case FINISHING = 'finishing';
    case QC = 'qc';

    public function label(): string
    {
        return $this === self::QC ? 'Quality Control' : ucfirst($this->value);
    }

    public function isQc(): bool
    {
        return $this === self::QC;
    }

    /** Typical duration used for delay detection. */
    public function defaultExpectedMinutes(): int
    {
        return match ($this) {
            self::CUTTING => 60,
            self::ASSEMBLY => 120,
            self::SANDING => 90,
            self::FINISHING => 240,
            self::QC => 30,
        };
    }

    /**
     * All stages in order, including QC.
     *
     * @return array<int, StageType>
     */
    public static function ordered(): array
    {
        return [self::CUTTING, self::ASSEMBLY, self::SANDING, self::FINISHING, self::QC];
    }

    /**
     * Production stages before QC.
     *
     * @return array<int, StageType>
     */
    public static function production(): array
    {
        return [self::CUTTING, self::ASSEMBLY, self::SANDING, self::FINISHING];
    }
}
