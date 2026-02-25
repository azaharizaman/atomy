<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class CostCalculationResult
{
    public function __construct(
        public string $estimatedMaterialCost,
        public string $estimatedLaborCost,
        public string $estimatedOverheadCost,
        public CurrencyCode $currency,
    ) {
    }

    public function getTotal(): string
    {
        return bcadd(
            bcadd($this->estimatedMaterialCost, $this->estimatedLaborCost, 4),
            $this->estimatedOverheadCost,
            4
        );
    }
}
