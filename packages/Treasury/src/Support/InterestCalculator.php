<?php

declare(strict_types=1);

namespace Nexus\Treasury\Support;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Common\ValueObjects\Money;

/**
 * Interest Calculator using Actual/365 Fixed day-count convention.
 */
final class InterestCalculator
{
    /**
     * Calculate simple interest using Actual/365 Fixed convention.
     *
     * @param Money $principal The principal amount
     * @param float $annualRate Annual interest rate as percentage (e.g., 5.0 for 5%)
     * @param DateTimeImmutable $startDate Start date of interest accrual
     * @param DateTimeImmutable $endDate End date of interest accrual
     * @return Money Calculated interest amount
     * @throws InvalidArgumentException If annualRate is negative
     */
    public static function calculateSimpleInterest(
        Money $principal,
        float $annualRate,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): Money {
        if ($annualRate < 0) {
            throw new InvalidArgumentException(sprintf(
                'Annual rate must be non-negative, got %f',
                $annualRate
            ));
        }

        $interval = $startDate->diff($endDate);
        $days = (int) $interval->days;
        
        if ($interval->invert === 1) {
            $days = -$days;
        }
        
        $years = $days / 365;
        $accrued = $principal->getAmount() * ($annualRate / 100) * $years;

        return Money::of($accrued, $principal->getCurrency());
    }
}
