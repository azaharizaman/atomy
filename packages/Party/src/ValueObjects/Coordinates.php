<?php

declare(strict_types=1);

namespace Nexus\Party\ValueObjects;

final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    /** @return array{latitude: float, longitude: float} */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
