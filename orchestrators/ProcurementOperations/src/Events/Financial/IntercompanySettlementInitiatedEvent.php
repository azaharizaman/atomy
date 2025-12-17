<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when intercompany settlement is initiated.
 */
final readonly class IntercompanySettlementInitiatedEvent
{
    public function __construct(
        public string $settlementId,
        public string $fromEntityId,
        public string $toEntityId,
        public Money $grossReceivables,
        public Money $grossPayables,
        public int $transactionCount,
        public string $initiatedBy,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
