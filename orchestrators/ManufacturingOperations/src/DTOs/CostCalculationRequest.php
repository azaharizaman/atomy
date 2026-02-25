<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class CostCalculationRequest
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public ?string $bomId = null,
        public ?string $routingId = null,
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException("quantity must be a positive number");
        }
    }
}
