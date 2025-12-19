<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use DateTimeImmutable;

/**
 * Event dispatched when a new settlement batch is created.
 */
final class SettlementBatchCreatedEvent extends SettlementBatchEvent
{
    public function __construct(
        string $batchId,
        string $tenantId,
        string $processorId,
        public readonly string $currency,
        DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($batchId, $tenantId, $processorId, $occurredAt);
    }
}
