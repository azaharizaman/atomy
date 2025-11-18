<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

/**
 * Thrown when attempting to post to a closed period
 */
final class PostingPeriodClosedException extends PeriodException
{
    public static function forPeriod(string $periodId, string $periodName): self
    {
        return new self("Cannot post to closed period: {$periodName} (ID: {$periodId})");
    }
}
