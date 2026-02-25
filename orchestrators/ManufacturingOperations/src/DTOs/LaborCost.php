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
    ) {
        if ($this->hours <= 0) {
            throw new \InvalidArgumentException("hours must be a positive number");
        }

        $decimalPattern = '/^\d+(\.\d+)?$/';
        if (!preg_match($decimalPattern, $this->hourlyRate)) {
            throw new \InvalidArgumentException("hourlyRate must be a valid non-negative decimal string");
        }
        if (!preg_match($decimalPattern, $this->totalCost)) {
            throw new \InvalidArgumentException("totalCost must be a valid non-negative decimal string");
        }
    }
}
