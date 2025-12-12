<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Tier levels for vendor portal rate limiting.
 */
enum VendorPortalTier: string
{
    /**
     * Standard tier - basic access.
     */
    case STANDARD = 'standard';

    /**
     * Premium tier - enhanced access.
     */
    case PREMIUM = 'premium';

    /**
     * Enterprise tier - highest access.
     */
    case ENTERPRISE = 'enterprise';

    /**
     * Suspended tier - temporarily blocked.
     */
    case SUSPENDED = 'suspended';

    /**
     * Get the rate limit per minute for this tier.
     */
    public function getRateLimitPerMinute(): int
    {
        return match ($this) {
            self::STANDARD => 100,
            self::PREMIUM => 500,
            self::ENTERPRISE => 1000,
            self::SUSPENDED => 0,
        };
    }

    /**
     * Get the monthly call threshold for potential tier upgrade.
     */
    public function getUpgradeThreshold(): ?int
    {
        return match ($this) {
            self::STANDARD => 10_000,   // > 10K calls → candidate for Premium
            self::PREMIUM => 50_000,     // > 50K calls → candidate for Enterprise
            self::ENTERPRISE => null,    // Already highest
            self::SUSPENDED => null,     // Cannot upgrade while suspended
        };
    }

    /**
     * Get the monthly call threshold for potential tier downgrade.
     */
    public function getDowngradeThreshold(): ?int
    {
        return match ($this) {
            self::STANDARD => null,      // Already lowest
            self::PREMIUM => 1_000,      // < 1K calls for 3 months → candidate for Standard
            self::ENTERPRISE => 10_000,  // < 10K calls for 3 months → candidate for Premium
            self::SUSPENDED => null,     // Handled separately
        };
    }

    /**
     * Get consecutive months below threshold required for downgrade.
     */
    public function getDowngradeConsecutiveMonths(): int
    {
        return match ($this) {
            self::PREMIUM, self::ENTERPRISE => 3,
            default => 0,
        };
    }

    /**
     * Get the next tier up from this one.
     */
    public function getNextTierUp(): ?self
    {
        return match ($this) {
            self::STANDARD => self::PREMIUM,
            self::PREMIUM => self::ENTERPRISE,
            self::ENTERPRISE => null,
            self::SUSPENDED => null,
        };
    }

    /**
     * Get the next tier down from this one.
     */
    public function getNextTierDown(): ?self
    {
        return match ($this) {
            self::ENTERPRISE => self::PREMIUM,
            self::PREMIUM => self::STANDARD,
            self::STANDARD => null,
            self::SUSPENDED => null,
        };
    }

    /**
     * Check if this tier allows API access.
     */
    public function allowsAccess(): bool
    {
        return $this !== self::SUSPENDED;
    }

    /**
     * Get a human-readable description.
     */
    public function description(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard vendor portal access',
            self::PREMIUM => 'Premium vendor portal access with higher rate limits',
            self::ENTERPRISE => 'Enterprise vendor portal access with highest rate limits',
            self::SUSPENDED => 'Portal access temporarily suspended',
        };
    }
}
