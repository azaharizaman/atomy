<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Enum representing recurrence frequencies for scheduled disbursements.
 *
 * Used for PAY-034: Disbursement scheduling support.
 */
enum RecurrenceFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUALLY = 'annually';

    /**
     * Get the interval in days for this frequency.
     *
     * Note: This is approximate for months/quarters/years.
     */
    public function approximateDays(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::BIWEEKLY => 14,
            self::MONTHLY => 30,
            self::QUARTERLY => 91,
            self::ANNUALLY => 365,
        };
    }

    /**
     * Get DateInterval string for this frequency.
     */
    public function toDateInterval(): \DateInterval
    {
        return match ($this) {
            self::DAILY => new \DateInterval('P1D'),
            self::WEEKLY => new \DateInterval('P1W'),
            self::BIWEEKLY => new \DateInterval('P2W'),
            self::MONTHLY => new \DateInterval('P1M'),
            self::QUARTERLY => new \DateInterval('P3M'),
            self::ANNUALLY => new \DateInterval('P1Y'),
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::BIWEEKLY => 'Bi-weekly',
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::ANNUALLY => 'Annually',
        };
    }
}
