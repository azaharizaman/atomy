<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class CostCalculationRequest
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public ?string $bomId = null,
        public ?string $routingId = null,
    ) {}
}
