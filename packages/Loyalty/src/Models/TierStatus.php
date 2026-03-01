<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Models;

use DateTimeImmutable;

/**
 * Value Object representing a member's current tier status.
 * Requirements: FUN-LOY-002, FUN-LOY-006, FUN-LOY-202, FUN-LOY-203
 */
final readonly class TierStatus
{
    /**
     * @param string $tierId Identifier for the tier (e.g., 'gold', 'platinum').
     * @param string $tierName Human-readable name (e.g., 'Gold Status').
     * @param DateTimeImmutable $attainedAt Timestamp when status was granted.
     * @param DateTimeImmutable|null $expiresAt When this status retention period ends.
     * @param array<string, mixed> $benefits List of benefit IDs or capabilities.
     */
    public function __construct(
        public string $tierId,
        public string $tierName,
        public DateTimeImmutable $attainedAt,
        public ?DateTimeImmutable $expiresAt = null,
        public array $benefits = []
    ) {
    }

    /**
     * Check if the current tier status is still valid based on the expiration date.
     */
    public function isValid(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if ($this->expiresAt === null) {
            return true;
        }

        return $now < $this->expiresAt;
    }
}
