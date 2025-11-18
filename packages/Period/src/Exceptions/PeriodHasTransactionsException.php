<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

/**
 * Thrown when attempting to delete a period that has transactions
 */
final class PeriodHasTransactionsException extends PeriodException
{
    public static function forPeriod(string $periodId, int $transactionCount): self
    {
        return new self(
            "Cannot delete period {$periodId} because it has {$transactionCount} associated transactions"
        );
    }
}
