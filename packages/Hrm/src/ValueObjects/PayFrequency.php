<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Pay frequency value object.
 */
enum PayFrequency: string
{
    case MONTHLY = 'monthly';
    case SEMI_MONTHLY = 'semi_monthly';
    case BI_WEEKLY = 'bi_weekly';
    case WEEKLY = 'weekly';
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    
    public function label(): string
    {
        return match($this) {
            self::MONTHLY => 'Monthly',
            self::SEMI_MONTHLY => 'Semi-Monthly (Twice per month)',
            self::BI_WEEKLY => 'Bi-Weekly (Every 2 weeks)',
            self::WEEKLY => 'Weekly',
            self::HOURLY => 'Hourly',
            self::DAILY => 'Daily',
        };
    }
    
    public function periodsPerYear(): int
    {
        return match($this) {
            self::MONTHLY => 12,
            self::SEMI_MONTHLY => 24,
            self::BI_WEEKLY => 26,
            self::WEEKLY => 52,
            self::HOURLY => 0, // Not applicable
            self::DAILY => 0, // Not applicable
        };
    }
}
