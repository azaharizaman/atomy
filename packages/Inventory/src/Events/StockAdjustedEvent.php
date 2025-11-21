<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

/**
 * Stock adjusted event
 * 
 * Published when stock is adjusted (cycle count, damage, etc.)
 */
final readonly class StockAdjustedEvent
{
    public function __construct(
        public string $productId,
        public string $warehouseId,
        public float $adjustmentQuantity,
        public string $reason,
        public \DateTimeImmutable $adjustedDate
    ) {
    }
}
