<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Loyalty\Contracts\PointCalculatorInterface;
use Nexus\Loyalty\Exceptions\AccrualCapExceededException;
use Nexus\Loyalty\Models\RoundingStrategy;

/**
 * Service for calculating point accrual for transactions and experiential events.
 * Requirements: FUN-LOY-001, FUN-LOY-005, FUN-LOY-101, FUN-LOY-102, FUN-LOY-103, FUN-LOY-104
 */
final readonly class PointCalculationEngine implements PointCalculatorInterface
{
    /**
     * @param RoundingStrategy $defaultRounding Strategy for fractional point handling.
     * @param float $baseRate Default rate of points per currency unit (e.g., 1.5 pts / $1).
     * @param int|null $maxPointsPerTransaction Safety cap per transaction.
     * @param bool $isMultiplicative Whether multiple multipliers are applied multiplicative or additive.
     */
    public function __construct(
        private RoundingStrategy $defaultRounding = RoundingStrategy::Floor,
        private float $baseRate = 1.0,
        private ?int $maxPointsPerTransaction = null,
        private bool $isMultiplicative = true
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function calculateAccrual(Money $amount, string $currency, array $multipliers = []): int
    {
        // 1. Calculate combined multiplier
        $combinedMultiplier = $this->calculateCombinedMultiplier($multipliers);

        // 2. Base points (Amount is in minor units/cents, so divide by 100 for base currency unit)
        $rawPoints = ($amount->amount / 100) * $this->baseRate * $combinedMultiplier;

        // 3. Apply rounding
        $finalPoints = $this->defaultRounding->apply((float) $rawPoints);

        // 4. Safety Cap Check
        if ($this->maxPointsPerTransaction !== null && $finalPoints > $this->maxPointsPerTransaction) {
            throw AccrualCapExceededException::forTransaction($finalPoints, $this->maxPointsPerTransaction);
        }

        return $finalPoints;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateExperientialReward(string $eventType, array $context = []): int
    {
        // Look up fixed points for non-transactional events
        // (In a real system, this might look up a registry or config)
        return match ($eventType) {
            'social_share' => 50,
            'eco_action' => 100,
            'on_time_payment' => 200, // BUS-LOY-004
            default => 0,
        };
    }

    /**
     * Calculate clawback points for a refund.
     * Requirement: FUN-LOY-104
     */
    public function calculateClawback(int $originalPoints, float $refundRatio): int
    {
        return (int) ceil($originalPoints * $refundRatio);
    }

    /**
     * Logic for combining multiple multipliers.
     * Requirement: FUN-LOY-102
     */
    private function calculateCombinedMultiplier(array $multipliers): float
    {
        if (empty($multipliers)) {
            return 1.0;
        }

        if ($this->isMultiplicative) {
            // Base * M1 * M2
            return array_reduce($multipliers, fn(float $carry, float $m) => $carry * $m, 1.0);
        }

        // Base + (Base * M_total) where M_total = sum(m - 1)
        // Example: Base rate with 1.5x and 2.0x additive is 1.0 + (0.5 + 1.0) = 2.5x total
        $totalModifier = array_reduce($multipliers, fn(float $carry, float $m) => $carry + ($m - 1.0), 0.0);
        return 1.0 + $totalModifier;
    }
}
