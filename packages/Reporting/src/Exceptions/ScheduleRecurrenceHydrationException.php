<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Exception thrown when hydration of ScheduleRecurrence from stored data fails.
 */
class ScheduleRecurrenceHydrationException extends ReportingException
{
    public static function forMalformedData(array $data, \Throwable $previous): self
    {
        return new self(
            "Failed to hydrate ScheduleRecurrence from stored data: " . $previous->getMessage(),
            0,
            $previous
        );
    }
}
