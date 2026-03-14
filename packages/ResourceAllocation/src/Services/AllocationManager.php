<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Services;

use Nexus\ResourceAllocation\Contracts\AllocationManagerInterface;
use Nexus\ResourceAllocation\Contracts\AllocationPersistInterface;
use Nexus\ResourceAllocation\Contracts\AllocationQueryInterface;
use Nexus\ResourceAllocation\Exceptions\OverallocationException;
use Nexus\ResourceAllocation\ValueObjects\AllocationSummary;

/**
 * Allocation with 100% per user per day cap (BUS-PRO-0084).
 */
final readonly class AllocationManager implements AllocationManagerInterface
{
    private const float MAX_PERCENTAGE_PER_DAY = 100.0;

    public function __construct(
        private AllocationQueryInterface $query,
        private AllocationPersistInterface $persist,
    ) {
    }

    public function allocate(AllocationSummary $allocation): void
    {
        $existing = $this->query->getTotalPercentageByUserAndDate(
            $allocation->userId,
            $allocation->date,
            $allocation->id
        );
        $newTotal = $existing + $allocation->percentage;
        if ($newTotal > self::MAX_PERCENTAGE_PER_DAY) {
            throw OverallocationException::dailyCapExceeded($allocation->userId, $newTotal);
        }
        $this->persist->persist($allocation);
    }
}
