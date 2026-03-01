<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use InvalidArgumentException;
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
     * @param array<string, int> $experientialRewards Map of event types to point values.
     * @param int $minorUnitsPerUnit Number of minor units per major unit (e.g., 100 for USD).
     */
    public function __construct(
        private RoundingStrategy $defaultRounding = RoundingStrategy::Floor,
        private float $baseRate = 1.0,
        private ?int $maxPointsPerTransaction = null,
        private bool $isMultiplicative = true,
        private array $experientialRewards = [
            'social_share' => 50,
            'eco_action' => 100,
            'on_time_payment' => 200,
        ],
        private int $minorUnitsPerUnit = 100
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function calculateAccrual(Money $amount, string $currency, array $multipliers = []): int
    {
        // Validate currency consistency
        if ($currency !== $amount->getCurrency()) {
            throw new InvalidArgumentException(sprintf(
                "Currency mismatch: expected %s but got %s",
                $amount->getCurrency(),
                $currency
            ));
        }

        // 1. Calculate combined multiplier
        $combinedMultiplier = $this->calculateCombinedMultiplier($multipliers);

        // 2. Base points using minor units getter and dynamic divisor
        $rawPoints = ($amount->getAmountInMinorUnits() / $this->minorUnitsPerUnit) * $this->baseRate * $combinedMultiplier;

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
        // Allow override via context or fallback to injected config
        return (int) ($context['points_override'] ?? $this->experientialRewards[$eventType] ?? 0);
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
        $totalModifier = array_reduce($multipliers, fn(float $carry, float $m) => $carry + ($m - 1.0), 0.0);
        return 1.0 + $totalModifier;
    }
}
