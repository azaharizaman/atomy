<?php

declare(strict_types=1);

namespace Nexus\Inventory\Events;

use Nexus\Inventory\Enums\IssueReason;

/**
 * Stock issued event
 * 
 * Published when stock is issued from warehouse
 * Consumed by: Nexus\Finance (COGS posting), Nexus\Manufacturing (material consumption)
 */
final readonly class StockIssuedEvent
{
    public function __construct(
        public string $productId,
        public string $warehouseId,
        public float $quantity,
        public float $costOfGoodsSold,
        public \DateTimeImmutable $issuedDate,
        public IssueReason $issueReason,
        public ?string $referenceId = null
    ) {
    }
}
