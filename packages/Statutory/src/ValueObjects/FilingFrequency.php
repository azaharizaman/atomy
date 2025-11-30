<?php

declare(strict_types=1);

namespace Nexus\Statutory\ValueObjects;

/**
 * Immutable value object representing filing frequency for statutory reports.
 */
enum FilingFrequency: string
{
    case MONTHLY = 'Monthly';
    case QUARTERLY = 'Quarterly';
    case SEMI_ANNUALLY = 'Semi-Annually';
    case ANNUALLY = 'Annually';
    case ON_DEMAND = 'On-Demand';
    case BIENNIAL = 'Biennial'; // Every 2 years

    /**
     * Get the number of months between filings.
     *
     * @return int Number of months (0 for on-demand)
     */
    public function getMonthInterval(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::SEMI_ANNUALLY => 6,
            self::ANNUALLY => 12,
            self::BIENNIAL => 24,
            self::ON_DEMAND => 0,
        };
    }

    /**
     * Check if this frequency requires regular scheduled filing.
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this !== self::ON_DEMAND;
    }

    /**
     * Get the number of filings per year.
     *
     * @return int Number of filings (0 for on-demand or biennial)
     */
    public function getFilingsPerYear(): int
    {
        return match ($this) {
            self::MONTHLY => 12,
            self::QUARTERLY => 4,
            self::SEMI_ANNUALLY => 2,
            self::ANNUALLY => 1,
            self::BIENNIAL => 0,
            self::ON_DEMAND => 0,
        };
    }
}
