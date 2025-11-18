<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

/**
 * Thrown when a user is not authorized to reopen a period
 */
final class PeriodReopeningUnauthorizedException extends PeriodException
{
    public static function forUser(string $userId, string $periodId): self
    {
        return new self("User {$userId} is not authorized to reopen period: {$periodId}");
    }
}
