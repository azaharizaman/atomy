<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class LaborCost
{
    public function __construct(
        public string $code,
        public string $description,
        public float $hours,
        public string $hourlyRate,
        public string $totalCost,
    ) {}
}
