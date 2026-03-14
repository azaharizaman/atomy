<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Contracts;

use Nexus\ResourceAllocation\ValueObjects\AllocationSummary;

/**
 * Allocation lifecycle. BUS-PRO-0084: allocation cannot exceed 100% per user per day.
 */
interface AllocationManagerInterface
{
    /**
     * Allocate capacity. Validates daily total does not exceed 100%.
     *
     * @throws \Nexus\ResourceAllocation\Exceptions\OverallocationException
     */
    public function allocate(AllocationSummary $allocation): void;
}
