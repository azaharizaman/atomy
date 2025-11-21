<?php

declare(strict_types=1);

namespace Nexus\Audit\ValueObjects;

use Nexus\Audit\Exceptions\InvalidRetentionPolicyException;

/**
 * Immutable value object representing audit log retention policy
 * Satisfies: BUS-AUD-0147, FUN-AUD-0194
 *
 * @package Nexus\Audit\ValueObjects
 */
final readonly class RetentionPolicy
{
    public const DEFAULT_DAYS = 90;
    public const MINIMUM_DAYS = 1;

    public function __construct(
        public int $days = self::DEFAULT_DAYS
    ) {
        if ($days < 0) {
            throw new InvalidRetentionPolicyException($days);
        }
    }

    /**
     * Calculate expiration date from a given creation date
     */
    public function calculateExpirationDate(\DateTimeImmutable $createdAt): \DateTimeImmutable
    {
        return $createdAt->modify("+{$this->days} days");
    }

    /**
     * Check if a log is expired based on its creation date
     */
    public function isExpired(\DateTimeImmutable $createdAt, ?\DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable();
        $expiresAt = $this->calculateExpirationDate($createdAt);
        return $now >= $expiresAt;
    }

    public function __toString(): string
    {
        return "{$this->days} days";
    }

    /**
     * Create default retention policy (90 days)
     */
    public static function default(): self
    {
        return new self(self::DEFAULT_DAYS);
    }

    /**
     * Create permanent retention (effectively infinite)
     */
    public static function permanent(): self
    {
        return new self(PHP_INT_MAX);
    }

    /**
     * Create retention policy with specific days
     */
    public static function days(int $days): self
    {
        return new self($days);
    }
}
