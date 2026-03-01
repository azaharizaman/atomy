<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

use Nexus\Loyalty\Models\LoyaltyProfile;

/**
 * Interface for checking if member tier status grants specific capabilities or benefits.
 */
interface BenefitProviderInterface
{
    /**
     * Check if a member tier grants a specific capability (e.g., 'free_shipping', 'priority_support').
     *
     * @param LoyaltyProfile $profile The member's loyalty profile.
     * @param string $capability The capability to check.
     * @return bool True if granted, false otherwise.
     */
    public function hasCapability(LoyaltyProfile $profile, string $capability): bool;

    /**
     * Get all benefits associated with a member's current tier status.
     *
     * @param LoyaltyProfile $profile The member's loyalty profile.
     * @return array<string, mixed> List of benefits granted.
     */
    public function getBenefits(LoyaltyProfile $profile): array;
}
