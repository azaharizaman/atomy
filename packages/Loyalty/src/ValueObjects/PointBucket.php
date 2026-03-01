<?php

declare(strict_types=1);

namespace Nexus\Loyalty\ValueObjects;

use DateTimeImmutable;

/**
 * Value Object representing a single accrual event and its remaining points.
 * Requirement: FUN-LOY-304 (FIFO Expiry Prioritization)
 */
final readonly class PointBucket
{
    /**
     * @param string $id Unique identifier for this bucket.
     * @param int $totalPoints Initial points awarded.
     * @param int $remainingPoints Current unredeemed points in this bucket.
     * @param DateTimeImmutable $accruedAt When points were earned.
     * @param DateTimeImmutable|null $expiresAt When points in this bucket expire.
     * @param string|null $sourceTransactionId Reference to origin transaction.
     */
    public function __construct(
        public string $id,
        public int $totalPoints,
        public int $remainingPoints,
        public DateTimeImmutable $accruedAt,
        public ?DateTimeImmutable $expiresAt = null,
        public ?string $sourceTransactionId = null
    ) {
    }

    /**
     * Check if points in this bucket are expired.
     */
    public function isExpired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $now >= $this->expiresAt;
    }

    /**
     * Check if this bucket has unredeemed points.
     */
    public function hasPoints(): bool
    {
        return $this->remainingPoints > 0;
    }

    /**
     * Create a new bucket with a reduced amount after redemption.
     */
    public function deduct(int $points): self
    {
        return new self(
            $this->id,
            $this->totalPoints,
            max(0, $this->remainingPoints - $points),
            $this->accruedAt,
            $this->expiresAt,
            $this->sourceTransactionId
        );
    }
}
