<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Services;

use DateTimeImmutable;
use Nexus\ResourceAllocation\Contracts\AllocationQueryInterface;
use Nexus\ResourceAllocation\Contracts\OverallocationCheckerInterface;

/**
 * Detects overallocation (REL-PRO-0402): total > 100% per user per day.
 */
final readonly class OverallocationCheckerService implements OverallocationCheckerInterface
{
    private const float CAP = 100.0;

    public function __construct(
        private AllocationQueryInterface $query,
    ) {
    }

    public function isOverallocated(string $userId, DateTimeImmutable $date): bool
    {
        $total = $this->query->getTotalPercentageByUserAndDate($userId, $date, null);
        return $total > self::CAP;
    }
}
