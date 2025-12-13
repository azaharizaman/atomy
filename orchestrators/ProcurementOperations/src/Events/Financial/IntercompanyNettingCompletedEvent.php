<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when intercompany netting is completed.
 */
final readonly class IntercompanyNettingCompletedEvent
{
    public function __construct(
        public string $settlementId,
        public string $fromEntityId,
        public string $toEntityId,
        public Money $netAmount,
        public string $netDirection, // RECEIVE, PAY, ZERO
        public float $nettingEfficiency,
        public int $transactionsNetted,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
