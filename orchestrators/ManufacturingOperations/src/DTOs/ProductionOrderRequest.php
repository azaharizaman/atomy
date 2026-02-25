<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class ProductionOrderRequest
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public string $uom,
        public \DateTimeImmutable $dueDate,
        public ProductionPriority $priority = ProductionPriority::Normal,
        public ?string $bomId = null,
        public ?string $routingId = null,
        public ?string $estimatedMaterialCost = null,
        public ?string $estimatedLaborCost = null,
        public ?string $estimatedOverheadCost = null,
        public ?CurrencyCode $currency = null,
        public ?string $warehouseId = null,
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException("quantity must be a positive number");
        }

        $decimalPattern = '/^\d+(\.\d+)?$/';
        
        $costs = [
            'estimatedMaterialCost' => $this->estimatedMaterialCost,
            'estimatedLaborCost' => $this->estimatedLaborCost,
            'estimatedOverheadCost' => $this->estimatedOverheadCost,
        ];

        $hasCost = false;
        foreach ($costs as $name => $value) {
            if ($value !== null) {
                if (!preg_match($decimalPattern, $value)) {
                    throw new \InvalidArgumentException("{$name} must be a valid decimal string");
                }
                $hasCost = true;
            }
        }

        if ($hasCost && $this->currency === null) {
            throw new \InvalidArgumentException("currency must be provided when cost estimations are present");
        }
    }
}
