<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Contracts\LoyaltySettingsInterface;
use Nexus\Loyalty\Contracts\RedemptionValidatorInterface;
use Nexus\Loyalty\Exceptions\InsufficientPointsException;
use Nexus\Loyalty\Exceptions\InvalidRedemptionRequestException;
use Nexus\Loyalty\Entities\LoyaltyProfile;

/**
 * Service for validating point redemption requests against balance and business constraints.
 * Requirements: FUN-LOY-003, FUN-LOY-301, FUN-LOY-302, FUN-LOY-303, FUN-LOY-304, FUN-LOY-402, SEC-LOY-002
 */
final readonly class RedemptionValidator implements RedemptionValidatorInterface
{
    /**
     * @param LoyaltySettingsInterface $settings Injected settings interface.
     */
    public function __construct(
        private LoyaltySettingsInterface $settings
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function validateRedemption(LoyaltyProfile $profile, int $pointsToRedeem, ?string $idempotencyToken = null): bool
    {
        // 0. Idempotency Check (FUN-LOY-402, SEC-LOY-002)
        if ($idempotencyToken !== null) {
            /** 
             * @todo Integration with an IdempotencyStore is required before production.
             * 1. Inject an IdempotencyStoreInterface via constructor.
             * 2. Check if token exists: if ($this->idempotencyStore->has($idempotencyToken)) return true;
             * 3. Ensure the store is updated upon successful redemption processing.
             */
        }

        // 1. Aggregate Balance Check (totalAvailable is already filtered for expiry in PointBalance)
        if ($profile->balance->totalAvailable < $pointsToRedeem) {
            throw InsufficientPointsException::forMember($profile->memberId, $pointsToRedeem, $profile->balance->totalAvailable);
        }

        // 2. Minimum balance threshold (FUN-LOY-302)
        if ($profile->balance->totalAvailable < $this->settings->getMinBalanceThreshold()) {
            throw InvalidRedemptionRequestException::forConstraint(
                sprintf("Minimum balance of %d required for redemptions.", $this->settings->getMinBalanceThreshold())
            );
        }

        // 3. Incremental multiples (FUN-LOY-303)
        if ($pointsToRedeem % $this->settings->getIncrementalStep() !== 0) {
            throw InvalidRedemptionRequestException::forConstraint(
                sprintf("Points must be redeemed in multiples of %d.", $this->settings->getIncrementalStep())
            );
        }

        // 4. FIFO Expiry Prioritization Logic Check (FUN-LOY-304)
        // Ensure non-expired, non-empty buckets can cover the redemption points
        $totalDeductible = 0;
        $now = new DateTimeImmutable();
        foreach ($profile->balance->buckets as $bucket) {
            if ($bucket->isExpired($now) || $bucket->remainingPoints <= 0) {
                continue;
            }

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
