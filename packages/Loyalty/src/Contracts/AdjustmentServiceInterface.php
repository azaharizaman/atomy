<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

use Nexus\Loyalty\Entities\LoyaltyProfile;

/**
 * Interface for manual point adjustments with mandatory auditing fields.
 */
interface AdjustmentServiceInterface
{
    /**
     * Perform a manual point adjustment with an audit trail.
     *
     * @param LoyaltyProfile $profile The loyalty profile to adjust.
     * @param int $points Number of points to add (positive) or subtract (negative).
     * @param string $reasonCode A standardized reason code for auditing.
     * @param string $adminId The ID of the administrator performing the action.
     * @param array<string, mixed> $metadata Additional context for the adjustment.
     * @return int The updated total point balance.
     */
    public function adjustPoints(
        LoyaltyProfile $profile,
        int $points,
        string $reasonCode,
        string $adminId,
        array $metadata = []
    ): int;
}
