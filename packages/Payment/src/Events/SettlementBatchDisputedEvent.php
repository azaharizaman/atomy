<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use DateTimeImmutable;

/**
 * Event dispatched when a settlement batch is disputed.
 */
final class SettlementBatchDisputedEvent extends SettlementBatchEvent
{
    public function __construct(
        string $batchId,
        string $tenantId,
        string $processorId,
        public readonly string $reason,
        DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($batchId, $tenantId, $processorId, $occurredAt);
    }
}
