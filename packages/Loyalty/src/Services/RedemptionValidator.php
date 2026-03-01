<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Contracts\IdempotencyStoreInterface;
use Nexus\Loyalty\Contracts\LoyaltySettingsInterface;
use Nexus\Loyalty\Contracts\RedemptionValidatorInterface;
use Nexus\Loyalty\Exceptions\InsufficientPointsException;
use Nexus\Loyalty\Exceptions\InvalidRedemptionRequestException;
use Nexus\Loyalty\Entities\LoyaltyProfile;
use RuntimeException;

/**
 * Service for validating point redemption requests against balance and business constraints.
 * Requirements: FUN-LOY-003, FUN-LOY-301, FUN-LOY-302, FUN-LOY-303, FUN-LOY-304, FUN-LOY-402, SEC-LOY-002
 */
final readonly class RedemptionValidator implements RedemptionValidatorInterface
{
    /**
     * @param LoyaltySettingsInterface $settings Injected settings interface.
     * @param IdempotencyStoreInterface $idempotencyStore Store for idempotency tokens.
     */
    public function __construct(
        private LoyaltySettingsInterface $settings,
        private IdempotencyStoreInterface $idempotencyStore
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function validateRedemption(LoyaltyProfile $profile, int $pointsToRedeem, ?string $idempotencyToken = null): bool
    {
        // 0. Idempotency Check (FUN-LOY-402, SEC-LOY-002)
        if ($idempotencyToken !== null && $this->idempotencyStore->has($idempotencyToken)) {
            return true;
        }

        // 1. Invariant Check: Ensure PointBalance is consistent with its buckets
        $this->ensureBalanceConsistency($profile);

        // 2. Aggregate Balance Check (totalAvailable is already filtered for expiry in PointBalance)
        if ($profile->balance->totalAvailable < $pointsToRedeem) {
            throw InsufficientPointsException::forMember($profile->memberId, $pointsToRedeem, $profile->balance->totalAvailable);
        }

        // 3. Minimum balance threshold (FUN-LOY-302)
        if ($profile->balance->totalAvailable < $this->settings->getMinBalanceThreshold()) {
            throw InvalidRedemptionRequestException::forConstraint(
                sprintf("Minimum balance of %d required for redemptions.", $this->settings->getMinBalanceThreshold())
            );
        }

        // 4. Incremental multiples (FUN-LOY-303)
        if ($pointsToRedeem % $this->settings->getIncrementalStep() !== 0) {
            throw InvalidRedemptionRequestException::forConstraint(
                sprintf("Points must be redeemed in multiples of %d.", $this->settings->getIncrementalStep())
            );
        }

        // 5. FIFO Expiry Prioritization Logic Check (FUN-LOY-304)
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

        // If validation passes and we have a token, mark it as used
        // Note: In a real system, the orchestrator would call mark() after successful processing
        if ($idempotencyToken !== null) {
            $this->idempotencyStore->mark($idempotencyToken);
        }

        return true;
    }

    /**
     * Ensure that totalAvailable is consistent with active buckets.
     * @throws RuntimeException If the balance is inconsistent.
     */
    private function ensureBalanceConsistency(LoyaltyProfile $profile): void
    {
        $now = new DateTimeImmutable();
        $sum = array_reduce($profile->balance->buckets, function (int $total, $bucket) use ($now) {
            return $total + ($bucket->isExpired($now) ? 0 : $bucket->remainingPoints);
        }, 0);

        if ($sum !== $profile->balance->totalAvailable) {
            throw new RuntimeException(sprintf(
                "Data Integrity Violation: PointBalance.totalAvailable (%d) does not match sum of active buckets (%d). " .
                "Profiles must be constructed via PointBalance::fromBuckets().",
                $profile->balance->totalAvailable,
                $sum
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function meetsMinimumThreshold(LoyaltyProfile $profile, int $minimumThreshold): bool
    {
        return $profile->balance->totalAvailable >= $minimumThreshold;
    }
}
