<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Contracts;

use DateTimeImmutable;
use Nexus\ResourceAllocation\ValueObjects\AllocationSummary;

/**
 * Read-only allocation query contract.
 */
interface AllocationQueryInterface
{
    /**
     * Sum of allocation percentage for user on date (for 100% cap check).
     * When updating, pass excludeAllocationId to exclude current record.
     */
    public function getTotalPercentageByUserAndDate(string $userId, DateTimeImmutable $date, ?string $excludeAllocationId = null): float;

    /** @return list<AllocationSummary> */
    public function getByUserAndDate(string $userId, DateTimeImmutable $date): array;
}
