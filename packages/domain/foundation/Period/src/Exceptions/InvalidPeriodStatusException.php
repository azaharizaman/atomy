<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

/**
 * Thrown when attempting an invalid status transition
 */
final class InvalidPeriodStatusException extends PeriodException
{
    public static function forTransition(string $currentStatus, string $newStatus): self
    {
        return new self("Cannot transition from {$currentStatus} to {$newStatus}");
    }
}
