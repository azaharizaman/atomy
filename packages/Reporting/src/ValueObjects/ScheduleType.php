<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

/**
 * Report scheduling frequency types.
 */
enum ScheduleType: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case CRON = 'cron';

    /**
     * Get a human-readable label for the schedule type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ONCE => 'One-time',
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
            self::CRON => 'Custom (Cron)',
        };
    }

    /**
     * Check if this schedule type requires a cron expression.
     */
    public function requiresCronExpression(): bool
    {
        return $this === self::CRON;
    }

    /**
     * Check if this schedule type is recurring.
     */
    public function isRecurring(): bool
    {
        return $this !== self::ONCE;
    }
}
