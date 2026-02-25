<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class ProductionOrder
{
    public function __construct(
        public string $id,
        public string $orderNumber,
        public string $productId,
        public float $quantity,
        public string $status, // Planned, Released, InProgress, Completed, Closed
        public \DateTimeImmutable $dueDate,
        public ?\DateTimeImmutable $startDate = null,
        public ?\DateTimeImmutable $completionDate = null,
        public ?string $reservationId = null,
    ) {}
}
