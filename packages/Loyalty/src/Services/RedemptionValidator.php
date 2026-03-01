<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use Nexus\Loyalty\Contracts\RedemptionValidatorInterface;
use Nexus\Loyalty\Exceptions\InsufficientPointsException;
use Nexus\Loyalty\Exceptions\InvalidRedemptionRequestException;
use Nexus\Loyalty\Models\LoyaltyProfile;

/**
 * Service for validating point redemption requests against balance and business constraints.
 * Requirements: FUN-LOY-003, FUN-LOY-301, FUN-LOY-302, FUN-LOY-303, FUN-LOY-304, FUN-LOY-402, SEC-LOY-002
 */
final readonly class RedemptionValidator implements RedemptionValidatorInterface
{
    /**
     * @param int $minBalanceThreshold Minimum required balance to redeem.
     * @param int $incrementalStep Step multiplier for redemptions (e.g., 500 pts).
     */
    public function __construct(
        private int $minBalanceThreshold = 1000,
        private int $incrementalStep = 100
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function validateRedemption(LoyaltyProfile $profile, int $pointsToRedeem, ?string $idempotencyToken = null): bool
    {
        // 1. Balance check
        if ($profile->balance->totalAvailable < $pointsToRedeem) {
            throw InsufficientPointsException::forMember($profile->memberId, $pointsToRedeem, $profile->balance->totalAvailable);
        }

        // 2. Minimum balance threshold (FUN-LOY-302)
        if ($profile->balance->totalAvailable < $this->minBalanceThreshold) {
            throw InvalidRedemptionRequestException::forConstraint(
                sprintf("Minimum balance of %d required for redemptions.", $this->minBalanceThreshold)
            );
        }

        // 3. Incremental multiples (FUN-LOY-303)
        if ($pointsToRedeem % $this->incrementalStep !== 0) {
            throw InvalidRedemptionRequestException::forConstraint(
                sprintf("Points must be redeemed in multiples of %d.", $this->incrementalStep)
            );
        }

        // 4. FIFO Expiry Prioritization Logic Check (FUN-LOY-304)
        // Ensure buckets can cover the redemption points
        $totalDeductible = 0;
        foreach ($profile->balance->buckets as $bucket) {
            $totalDeductible += $bucket->remainingPoints;
            if ($totalDeductible >= $pointsToRedeem) {
                break;
            }
        }

        if ($totalDeductible < $pointsToRedeem) {
            throw InsufficientPointsException::forMember($profile->memberId, $pointsToRedeem, $totalDeductible);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function meetsMinimumThreshold(LoyaltyProfile $profile, int $minimumThreshold): bool
    {
        return $profile->balance->totalAvailable >= $minimumThreshold;
    }
}
