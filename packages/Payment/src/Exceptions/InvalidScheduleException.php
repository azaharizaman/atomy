<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Exception thrown when a disbursement schedule is invalid.
 *
 * Used for PAY-034: Disbursement scheduling support.
 */
final class InvalidScheduleException extends PaymentException
{
    /**
     * Create exception for scheduled date in the past.
     */
    public static function scheduledDateInPast(\DateTimeImmutable $date): self
    {
        return new self(sprintf(
            'Scheduled date %s is in the past',
            $date->format('Y-m-d H:i:s'),
        ));
    }

    /**
     * Create exception for end date before start date.
     */
    public static function endDateBeforeStartDate(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): self {
        return new self(sprintf(
            'Recurrence end date %s cannot be before start date %s',
            $endDate->format('Y-m-d'),
            $startDate->format('Y-m-d'),
        ));
    }

    /**
     * Create exception for invalid max occurrences.
     */
    public static function invalidMaxOccurrences(int $value): self
    {
        return new self(sprintf(
            'Max occurrences must be at least 1, got %d',
            $value,
        ));
    }

    /**
     * Create exception for missing required schedule date.
     */
    public static function scheduledDateRequired(): self
    {
        return new self('Scheduled or recurring disbursements require a scheduled date');
    }

    /**
     * Create exception for missing recurrence frequency.
     */
    public static function recurrenceFrequencyRequired(): self
    {
        return new self('Recurring disbursements require a recurrence frequency');
    }

    /**
     * Create exception for schedule already completed.
     */
    public static function scheduleCompleted(): self
    {
        return new self('This disbursement schedule has no more occurrences');
    }

    /**
     * Create exception for non-recurring schedule.
     */
    public static function notRecurring(): self
    {
        return new self('This disbursement schedule is not recurring');
    }

    /**
     * Create exception for no more occurrences.
     */
    public static function noMoreOccurrences(): self
    {
        return new self('This recurring disbursement has no more occurrences');
    }
}
