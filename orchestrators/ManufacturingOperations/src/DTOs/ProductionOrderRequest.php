<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class ProductionOrderRequest
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public \DateTimeImmutable $dueDate,
        public ProductionPriority $priority = ProductionPriority::Normal,
        public ?string $bomId = null,
        public ?string $routingId = null,
        public ?string $estimatedMaterialCost = null,
        public ?string $estimatedLaborCost = null,
        public ?string $estimatedOverheadCost = null,
        public ?CurrencyCode $currency = null,
    ) {}
}
