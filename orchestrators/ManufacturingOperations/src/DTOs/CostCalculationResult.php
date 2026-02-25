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
        $decimalPattern = '/^[+-]?\d+(\.\d+)?$/';
        if (!preg_match($decimalPattern, $this->estimatedMaterialCost)) {
            throw new \InvalidArgumentException("estimatedMaterialCost must be a valid decimal string");
        }
        if (!preg_match($decimalPattern, $this->estimatedLaborCost)) {
            throw new \InvalidArgumentException("estimatedLaborCost must be a valid decimal string");
        }
        if (!preg_match($decimalPattern, $this->estimatedOverheadCost)) {
            throw new \InvalidArgumentException("estimatedOverheadCost must be a valid decimal string");
        }
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
