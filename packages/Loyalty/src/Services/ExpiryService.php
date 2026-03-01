<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Models\PointBucket;

/**
 * Service for calculating point expiry dates and evaluating expired points for write-off.
 * Requirements: FUN-LOY-004, COM-LOY-002
 */
final readonly class ExpiryService
{
    /**
     * @param int $defaultExpiryMonths Standard points lifespan.
     */
    public function __construct(
        private int $defaultExpiryMonths = 12
    ) {
    }

    /**
     * Calculate the expiry date for a new accrual.
     *
     * @param DateTimeImmutable $accruedAt The date of accrual.
     * @return DateTimeImmutable The calculated expiry date.
     */
    public function calculateExpiryDate(DateTimeImmutable $accruedAt): DateTimeImmutable
    {
        return $accruedAt->modify(sprintf("+%d months", $this->defaultExpiryMonths));
    }

    /**
     * Identify points in a collection of buckets that are nearing expiry.
     *
     * @param array<PointBucket> $buckets Member's point buckets.
     * @param int $warningDays Threshold in days to consider 'soon'.
     * @param DateTimeImmutable $now Evaluation time.
     * @return array<PointBucket>
     */
    public function getExpiringSoon(array $buckets, int $warningDays = 30, DateTimeImmutable $now = new DateTimeImmutable()): array
    {
        $warningThreshold = $now->modify(sprintf("+%d days", $warningDays));

        return array_filter($buckets, function (PointBucket $bucket) use ($now, $warningThreshold) {
            if ($bucket->expiresAt === null || $bucket->isExpired($now)) {
                return false;
            }

            return $bucket->expiresAt <= $warningThreshold;
        });
    }

    /**
     * Calculate total points to be written off due to expiry.
     * Requirement: COM-LOY-002
     *
     * @param array<PointBucket> $buckets Member's point buckets.
     * @param DateTimeImmutable $now Evaluation time.
     * @return int Total points expired.
     */
    public function calculateExpiredPoints(array $buckets, DateTimeImmutable $now = new DateTimeImmutable()): int
    {
        return array_reduce($buckets, function (int $total, PointBucket $bucket) use ($now) {
            return $total + ($bucket->isExpired($now) ? $bucket->remainingPoints : 0);
        }, 0);
    }
}
