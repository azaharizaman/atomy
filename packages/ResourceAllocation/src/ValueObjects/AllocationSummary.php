<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\ValueObjects;

use DateTimeImmutable;

/**
 * Single allocation record (FUN-PRO-0571). BUS-PRO-0084: total per user per day cannot exceed 100%.
 */
final readonly class AllocationSummary
{
    public function __construct(
        public string $id,
        public string $userId,
        public DateTimeImmutable $date,
        /** Allocation percentage 0-100 */
        public float $percentage,
        /** Optional reference to assignment/demand (e.g. project task) */
        public ?string $assignmentId = null,
    ) {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException(sprintf(
                'Allocation percentage must be between 0 and 100, got %s.',
                $percentage
            ));
        }
        if ($userId === '') {
            throw new \InvalidArgumentException('User id cannot be empty.');
        }
    }
}
