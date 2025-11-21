<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

use Nexus\Inventory\Enums\TransferStatus;

/**
 * Stock transferred event
 * 
 * Published when stock transfer state changes
 */
final readonly class StockTransferredEvent
{
    public function __construct(
        public string $transferId,
        public string $productId,
        public string $fromWarehouseId,
        public string $toWarehouseId,
        public float $quantity,
        public TransferStatus $status,
        public \DateTimeImmutable $occurredAt
    ) {
    }
}
