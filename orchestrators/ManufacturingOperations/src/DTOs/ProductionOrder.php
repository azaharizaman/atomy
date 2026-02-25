<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class ProductionOrder
{
    public function __construct(
        public string $id,
        public string $orderNumber,
        public string $productId,
        public float $quantity,
        public ProductionOrderStatus $status,
        public \DateTimeImmutable $dueDate,
        public ?\DateTimeImmutable $startDate = null,
        public ?\DateTimeImmutable $completionDate = null,
        public ?string $reservationId = null,
        public ?string $bomId = null,
    ) {}
}
