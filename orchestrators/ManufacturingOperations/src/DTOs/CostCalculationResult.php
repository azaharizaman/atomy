<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class CostCalculationResult
{
    public function __construct(
        public float $estimatedMaterialCost,
        public float $estimatedLaborCost,
        public float $estimatedOverheadCost,
        public string $currency,
    ) {
    }

    public function getTotal(): float
    {
        return $this->estimatedMaterialCost + $this->estimatedLaborCost + $this->estimatedOverheadCost;
    }
}
