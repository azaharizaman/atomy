<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Models;

use Nexus\Common\ValueObjects\Money;

/**
 * Value Object defining point valuation for redemptions.
 * Requirement: FUN-LOY-301
 */
final readonly class RedemptionRatio
{
    /**
     * @param int $pointsPerUnit The number of points that equals 1 unit of the given currency.
     * @param string $currency The currency being valued (e.g., 'USD').
     */
    public function __construct(
        public int $pointsPerUnit,
        public string $currency
    ) {
    }

    /**
     * Calculate the monetary value of a given point amount.
     *
     * @param int $points The number of points to value.
     * @return Money The resulting monetary value.
     */
    public function calculateValue(int $points): Money
    {
        $amountInMinorUnits = (int) round(($points / $this->pointsPerUnit) * 100);
        return new Money($amountInMinorUnits, $this->currency);
    }

    /**
     * Calculate how many points are needed to represent a given monetary amount.
     *
     * @param Money $amount The monetary amount.
     * @return int The required number of points.
     */
    public function calculatePoints(Money $amount): int
    {
        return (int) round(($amount->amount / 100) * $this->pointsPerUnit);
    }
}
