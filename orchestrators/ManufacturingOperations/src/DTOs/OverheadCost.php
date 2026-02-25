<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class OverheadCost
{
    public function __construct(
        public string $code,
        public string $description,
        public string $totalCost,
    ) {
        $decimalPattern = '/^\d+(\.\d+)?$/';
        if (!preg_match($decimalPattern, $this->totalCost)) {
            throw new \InvalidArgumentException("totalCost must be a valid non-negative decimal string");
        }
    }
}
