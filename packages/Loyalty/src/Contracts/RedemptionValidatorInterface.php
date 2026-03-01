<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

use Nexus\Loyalty\Models\LoyaltyProfile;
use Nexus\Loyalty\Models\RedemptionRatio;

/**
 * Interface for validating point redemption requests against member balance and constraints.
 */
interface RedemptionValidatorInterface
{
    /**
     * Validate if a redemption request is permissible for a member.
     *
     * @param LoyaltyProfile $profile The member's loyalty profile.
     * @param int $pointsToRedeem The number of points requested for redemption.
     * @param string|null $idempotencyToken An optional token to prevent double-spending.
     * @return bool True if valid, false otherwise.
     * @throws \Nexus\Loyalty\Exceptions\InsufficientPointsException If the balance is too low.
     * @throws \Nexus\Loyalty\Exceptions\InvalidRedemptionRequestException If request violates constraints.
     */
    public function validateRedemption(LoyaltyProfile $profile, int $pointsToRedeem, ?string $idempotencyToken = null): bool;

    /**
     * Check if a member meets the minimum point threshold required for ANY redemption.
     *
     * @param LoyaltyProfile $profile The member's loyalty profile.
     * @param int $minimumThreshold The minimum required balance.
     * @return bool
     */
    public function meetsMinimumThreshold(LoyaltyProfile $profile, int $minimumThreshold): bool;
}
