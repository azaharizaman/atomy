<?php

declare(strict_types=1);

namespace Nexus\Loyalty\ValueObjects;

use InvalidArgumentException;
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
     * @param int $minorUnitsPerUnit Number of minor units per major unit (e.g., 100 for USD).
     * @throws InvalidArgumentException If pointsPerUnit or minorUnitsPerUnit are not positive.
     */
    public function __construct(
        public int $pointsPerUnit,
        public string $currency,
        public int $minorUnitsPerUnit = 100
    ) {
        if ($this->pointsPerUnit <= 0) {
            throw new InvalidArgumentException("pointsPerUnit must be greater than 0");
        }

        if ($this->minorUnitsPerUnit <= 0) {
            throw new InvalidArgumentException("minorUnitsPerUnit must be greater than 0");
        }
    }

    /**
     * Calculate the monetary value of a given point amount.
     *
     * @param int $points The number of points to value.
     * @return Money The resulting monetary value.
     */
    public function calculateValue(int $points): Money
    {
        $amountInMinorUnits = (int) round(($points / $this->pointsPerUnit) * $this->minorUnitsPerUnit);
        return new Money($amountInMinorUnits, $this->currency);
    }

    /**
     * Calculate how many points are needed to represent a given monetary amount.
     *
     * @param Money $amount The monetary amount.
     * @return int The required number of points.
     * @throws InvalidArgumentException If the money currency does not match the ratio currency.
     */
    public function calculatePoints(Money $amount): int
    {
        if ($amount->getCurrency() !== $this->currency) {
            throw new InvalidArgumentException(sprintf(
                "Currency mismatch: Money object has %s, but RedemptionRatio expects %s",
                $amount->getCurrency(),
                $this->currency
            ));
        }

        // Convert cents to units before multiplying by pointsPerUnit
        return (int) round(($amount->getAmountInMinorUnits() / $this->minorUnitsPerUnit) * $this->pointsPerUnit);
    }
}
