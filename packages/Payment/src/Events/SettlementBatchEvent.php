<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use DateTimeImmutable;

/**
 * Base class for settlement batch events.
 */
abstract class SettlementBatchEvent
{
    public function __construct(
        public readonly string $batchId,
        public readonly string $tenantId,
        public readonly string $processorId,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
