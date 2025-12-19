<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a settlement batch is closed.
 */
final class SettlementBatchClosedEvent extends SettlementBatchEvent
{
    public function __construct(
        string $batchId,
        string $tenantId,
        string $processorId,
        public readonly int $paymentCount,
        public readonly Money $grossAmount,
        public readonly Money $totalFees,
        public readonly Money $netAmount,
        DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($batchId, $tenantId, $processorId, $occurredAt);
    }
}
