<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Contracts;

use DateTimeImmutable;

/**
 * Overallocation detection and double-booking prevention (REL-PRO-0402).
 */
interface OverallocationCheckerInterface
{
    /**
     * Whether the user is overallocated on the given date (total > 100%).
     */
    public function isOverallocated(string $userId, DateTimeImmutable $date): bool;
}
