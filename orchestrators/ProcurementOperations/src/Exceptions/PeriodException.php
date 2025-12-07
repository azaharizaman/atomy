<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for period-related errors.
 */
class PeriodException extends ProcurementOperationsException
{
    /**
     * Create exception for period not found.
     */
    public static function notFound(\DateTimeImmutable $date): self
    {
        return new self(
            sprintf('No fiscal period found for date: %s', $date->format('Y-m-d'))
        );
    }

    /**
     * Create exception for period closed.
     */
    public static function periodClosed(string $periodId, \DateTimeImmutable $date): self
    {
        return new self(
            sprintf(
                'Fiscal period %s is closed. Cannot post transaction for date: %s',
                $periodId,
                $date->format('Y-m-d')
            )
        );
    }

    /**
     * Create exception for period locked.
     */
    public static function periodLocked(string $periodId): self
    {
        return new self(
            sprintf('Fiscal period %s is locked', $periodId)
        );
    }
}
