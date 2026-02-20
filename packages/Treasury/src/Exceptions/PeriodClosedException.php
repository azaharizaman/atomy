<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

use DateTimeImmutable;

final class PeriodClosedException extends TreasuryException
{
    public static function forDate(DateTimeImmutable $date): self
    {
        return new self("Period is closed for date: {$date->format('Y-m-d')}");
    }

    public static function forPeriodId(string $periodId): self
    {
        return new self("Period is closed: {$periodId}");
    }

    public static function cannotPostToClosedPeriod(string $periodId, string $operation): self
    {
        return new self(
            "Cannot perform {$operation} on closed period: {$periodId}"
        );
    }

    public static function fiscalYearClosed(string $fiscalYear): self
    {
        return new self("Fiscal year {$fiscalYear} is closed for treasury operations");
    }
}
