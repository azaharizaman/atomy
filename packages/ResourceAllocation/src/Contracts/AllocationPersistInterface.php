<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Contracts;

use Nexus\ResourceAllocation\ValueObjects\AllocationSummary;

/**
 * Allocation persistence (write side).
 */
interface AllocationPersistInterface
{
    public function persist(AllocationSummary $allocation): void;
}
