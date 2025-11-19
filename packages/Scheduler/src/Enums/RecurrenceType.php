<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Enums;

/**
 * Recurrence Type Enum
 *
 * Defines how a scheduled job should repeat.
 */
enum RecurrenceType: string
{
    case ONCE = 'once';
    case MINUTELY = 'minutely';
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case CRON = 'cron';
    
    /**
     * Check if this recurrence type requires a cron expression
     */
    public function requiresCronExpression(): bool
    {
        return $this === self::CRON;
    }
    
    /**
     * Check if this recurrence type requires an interval value
     */
    public function requiresInterval(): bool
    {
        return in_array($this, [
            self::MINUTELY,
            self::HOURLY,
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
            self::YEARLY,
        ], true);
    }
    
    /**
     * Check if this is a repeating schedule
     */
    public function isRepeating(): bool
    {
        return $this !== self::ONCE;
    }
    
    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::ONCE => 'One Time',
            self::MINUTELY => 'Every Minute',
            self::HOURLY => 'Hourly',
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
            self::CRON => 'Custom (Cron)',
        };
    }
    
    /**
     * Get the base interval in seconds
     */
    public function getBaseIntervalSeconds(): ?int
    {
        return match($this) {
            self::MINUTELY => 60,
            self::HOURLY => 3600,
            self::DAILY => 86400,
            self::WEEKLY => 604800,
            self::MONTHLY => 2592000,  // Approximate (30 days)
            self::YEARLY => 31536000,  // Approximate (365 days)
            self::ONCE, self::CRON => null,
        };
    }
}
