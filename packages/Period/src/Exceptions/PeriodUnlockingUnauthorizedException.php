<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a user attempts to unlock a period without authorization
 */
final class PeriodUnlockingUnauthorizedException extends RuntimeException implements PeriodException
{
    public static function forUser(string $userId, string $periodId): self
    {
        return new self(
            "User {$userId} is not authorized to unlock period {$periodId}. " .
            "This operation requires special administrative privileges."
        );
    }
}
