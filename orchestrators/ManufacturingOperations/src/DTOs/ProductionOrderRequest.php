<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class ProductionOrderRequest
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public \DateTimeImmutable $dueDate,
        public string $priority = 'normal',
        public ?string $bomId = null,
        public ?string $routingId = null,
    ) {}
}
