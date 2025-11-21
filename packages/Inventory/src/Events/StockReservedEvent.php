<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

/**
 * Stock reserved event
 * 
 * Published when stock is reserved for order/work order
 */
final readonly class StockReservedEvent
{
    public function __construct(
        public string $reservationId,
        public string $productId,
        public string $warehouseId,
        public float $quantity,
        public string $referenceType,
        public string $referenceId,
        public \DateTimeImmutable $reservedUntil,
        public \DateTimeImmutable $reservedDate
    ) {
    }
}
