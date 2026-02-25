<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class MaterialCost
{
    public function __construct(
        public string $productId,
        public string $totalCost,
        public CurrencyCode $currency,
        public float $quantity,
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException("quantity must be a positive number");
        }
        
        $decimalPattern = '/^\d+(\.\d+)?$/';
        if (!preg_match($decimalPattern, $this->totalCost)) {
            throw new \InvalidArgumentException("totalCost must be a valid non-negative decimal string");
        }
    }
}
