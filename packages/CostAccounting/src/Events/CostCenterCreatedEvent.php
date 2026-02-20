<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Events;

use Nexus\CostAccounting\Enums\CostCenterStatus;

/**
 * Cost Center Created Event
 * 
 * Dispatched when a new cost center is created.
 */
class CostCenterCreatedEvent
{
    public function __construct(
        public readonly string $costCenterId,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $parentCostCenterId,
        public readonly CostCenterStatus $status,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}
}
