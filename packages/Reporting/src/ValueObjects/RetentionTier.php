<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

/**
 * Report storage retention tiers.
 *
 * Implements the 3-tier lifecycle:
 * - Active: 90 days in hot storage (frequent access)
 * - Archived: 7 years in deep archive (compliance/legal)
 * - Purged: Permanently deleted (GDPR/data minimization)
 */
enum RetentionTier: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
    case PURGED = 'purged';

    /**
     * Get a human-readable label for the tier.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active (Hot Storage)',
            self::ARCHIVED => 'Archived (Deep Storage)',
            self::PURGED => 'Purged (Deleted)',
        };
    }

    /**
     * Get the duration (in days) for this tier before transitioning to the next.
     *
     * @return int|null Number of days, or null if terminal tier
     */
    public function durationDays(): ?int
    {
        return match ($this) {
            self::ACTIVE => 90,
            self::ARCHIVED => 2555, // ~7 years (365 * 7 = 2555 days, ignoring leap years)
            self::PURGED => null,   // Terminal state
        };
    }

    /**
     * Get the next tier in the lifecycle.
     */
    public function nextTier(): ?self
    {
        return match ($this) {
            self::ACTIVE => self::ARCHIVED,
            self::ARCHIVED => self::PURGED,
            self::PURGED => null,
        };
    }

    /**
     * Check if files in this tier are accessible for download.
     */
    public function isAccessible(): bool
    {
        return match ($this) {
            self::ACTIVE, self::ARCHIVED => true,
            self::PURGED => false,
        };
    }

    /**
     * Get the recommended storage class (for cloud providers).
     */
    public function storageClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'STANDARD',
            self::ARCHIVED => 'GLACIER',
            self::PURGED => 'DELETED',
        };
    }
}
