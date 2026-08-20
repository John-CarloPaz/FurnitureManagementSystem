<?php

namespace App\Domain\Dss\Support;

class RouteStop
{
    public function __construct(
        public readonly int|string $id,
        public readonly string $label,
        public readonly float $lat,
        public readonly float $lng,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'label' => $this->label, 'lat' => $this->lat, 'lng' => $this->lng];
    }
}
