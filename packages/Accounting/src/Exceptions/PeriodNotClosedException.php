<?php

declare(strict_types=1);

namespace Nexus\Accounting\Exceptions;

/**
 * Exception thrown when a period is not properly closed.
 */
final class PeriodNotClosedException extends \RuntimeException
{
    public static function forPeriod(string $periodId): self
    {
        return new self("Period '{$periodId}' is not closed or has validation errors.");
    }

    public static function withValidationErrors(string $periodId, array $errors): self
    {
        $errorList = implode(', ', $errors);
        return new self(
            "Period '{$periodId}' cannot be closed due to validation errors: {$errorList}"
        );
    }

    public static function alreadyClosed(string $periodId): self
    {
        return new self("Period '{$periodId}' is already closed.");
    }
}
