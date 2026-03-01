<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

use Nexus\Loyalty\Models\LoyaltyProfile;
use Nexus\Loyalty\Models\TierStatus;

/**
 * Interface for evaluating and managing loyalty tier status.
 */
interface TierManagerInterface
{
    /**
     * Evaluate a member's eligibility for tier progression.
     *
     * @param LoyaltyProfile $profile The current loyalty profile.
     * @return TierStatus The newly evaluated tier status.
     */
    public function evaluateTierProgression(LoyaltyProfile $profile): TierStatus;

    /**
     * Recalculate tier status based on historical activity within a look-back window.
     *
     * @param LoyaltyProfile $profile The current loyalty profile.
     * @param int $lookBackDays Number of days for evaluation window (e.g., 365).
     * @return TierStatus The recalculated tier status.
     */
    public function recalculateTierStatus(LoyaltyProfile $profile, int $lookBackDays): TierStatus;
}
