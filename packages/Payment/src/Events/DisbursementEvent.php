<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

/**
 * Base class for disbursement domain events.
 */
abstract class DisbursementEvent
{
    public function __construct(
        public readonly string $disbursementId,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
