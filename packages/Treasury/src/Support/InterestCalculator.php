<?php

declare(strict_types=1);

namespace Nexus\Treasury\Support;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

final class InterestCalculator
{
    public static function calculateSimpleInterest(
        Money $principal,
        float $annualRate,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): Money {
        $days = (int) $startDate->diff($endDate)->days;
        $years = $days / 365;
        $accrued = $principal->getAmount() * ($annualRate / 100) * $years;

        return Money::of($accrued, $principal->getCurrency());
    }
}
