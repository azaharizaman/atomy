<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a settlement batch is reconciled.
 */
final class SettlementBatchReconciledEvent extends SettlementBatchEvent
{
    public function __construct(
        string $batchId,
        string $tenantId,
        string $processorId,
        public readonly Money $expectedAmount,
        public readonly Money $actualAmount,
        public readonly Money $discrepancy,
        public readonly bool $hasDiscrepancy,
        DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($batchId, $tenantId, $processorId, $occurredAt);
    }
}
