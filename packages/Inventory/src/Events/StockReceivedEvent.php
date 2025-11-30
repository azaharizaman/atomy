<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

/**
 * Stock received event
 * 
 * Published when stock is received into warehouse
 * Consumed by: Nexus\Finance (GL posting), Nexus\Intelligence (demand forecasting)
 */
final readonly class StockReceivedEvent
{
    public function __construct(
        public string $productId,
        public string $warehouseId,
        public float $quantity,
        public float $unitCost,
        public float $totalValue,
        public \DateTimeImmutable $receivedDate,
        public ?string $grnId = null,
        public ?string $lotId = null
    ) {
    }
}
