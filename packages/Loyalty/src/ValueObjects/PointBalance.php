<?php

declare(strict_types=1);

namespace Nexus\Loyalty\ValueObjects;

use DateTimeImmutable;
use Nexus\Loyalty\ValueObjects\PointBucket;

/**
 * Value Object representing a collection of point buckets and the aggregate balance.
 * Requirement: BUS-LOY-002 (Points as Integers)
 */
final readonly class PointBalance
{
    /**
     * @param int $totalAvailable Points currently available for redemption. 
     *                             MUST match the sum of remaining points in non-expired buckets.
     *                             Use PointBalance::fromBuckets() to ensure this consistency.
     * @param int $lifetimeEarned Total points ever accrued by the member.
     * @param array<PointBucket> $buckets Collection of individual accrual buckets.
     */
    public function __construct(
        public int $totalAvailable,
        public int $lifetimeEarned,
        public array $buckets = []
    ) {
    }

    /**
     * Factory to calculate balance from a list of buckets.
     *
     * @param array<PointBucket> $buckets List of buckets.
     * @param int $lifetimeEarned Cumulative points earned over the member's life.
     * @param DateTimeImmutable $now Current time for expiry evaluation.
     * @return self
     */
    public static function fromBuckets(array $buckets, int $lifetimeEarned, DateTimeImmutable $now = new DateTimeImmutable()): self
    {
        $totalAvailable = 0;
        $validBuckets = [];

        foreach ($buckets as $bucket) {
            if (!$bucket->isExpired($now) && $bucket->hasPoints()) {
                $totalAvailable += $bucket->remainingPoints;
                $validBuckets[] = $bucket;
            }
        }

        return new self($totalAvailable, $lifetimeEarned, $validBuckets);
    }
}
