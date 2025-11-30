<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

/**
 * Lot assigned event
 * 
 * Published when lot is assigned to stock receipt
 */
final readonly class LotAssignedEvent
{
    public function __construct(
        public string $lotId,
        public string $lotNumber,
        public string $productId,
        public string $warehouseId,
        public float $quantity,
        public \DateTimeImmutable $expiryDate,
        public \DateTimeImmutable $assignedDate
    ) {
    }
}
