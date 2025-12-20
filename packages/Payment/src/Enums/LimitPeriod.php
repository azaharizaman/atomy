<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Enum representing limit periods for disbursement controls.
 *
 * Used for PAY-035: Disbursement limits and controls.
 */
enum LimitPeriod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUALLY = 'annually';
    case PER_TRANSACTION = 'per_transaction';

    /**
     * Get period start date for the current period.
     */
    public function getPeriodStart(\DateTimeImmutable $referenceDate): \DateTimeImmutable
    {
        return match ($this) {
            self::DAILY => $referenceDate->setTime(0, 0, 0),
            self::WEEKLY => $referenceDate->modify('monday this week')->setTime(0, 0, 0),
            self::MONTHLY => $referenceDate->modify('first day of this month')->setTime(0, 0, 0),
            self::QUARTERLY => $this->getQuarterStart($referenceDate),
            self::ANNUALLY => $referenceDate->modify('first day of january this year')->setTime(0, 0, 0),
            self::PER_TRANSACTION => $referenceDate,
        };
    }

    /**
     * Get period end date for the current period.
     */
    public function getPeriodEnd(\DateTimeImmutable $referenceDate): \DateTimeImmutable
    {
        return match ($this) {
            self::DAILY => $referenceDate->setTime(23, 59, 59),
            self::WEEKLY => $referenceDate->modify('sunday this week')->setTime(23, 59, 59),
            self::MONTHLY => $referenceDate->modify('last day of this month')->setTime(23, 59, 59),
            self::QUARTERLY => $this->getQuarterEnd($referenceDate),
            self::ANNUALLY => $referenceDate->modify('last day of december this year')->setTime(23, 59, 59),
            self::PER_TRANSACTION => $referenceDate,
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
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::ANNUALLY => 'Annually',
            self::PER_TRANSACTION => 'Per Transaction',
        };
    }

    /**
     * Get the start of the quarter for a given date.
     */
    private function getQuarterStart(\DateTimeImmutable $date): \DateTimeImmutable
    {
        $month = (int) $date->format('n');
        $quarterStartMonth = (int) (floor(($month - 1) / 3) * 3 + 1);

        return $date->setDate(
            (int) $date->format('Y'),
            $quarterStartMonth,
            1
        )->setTime(0, 0, 0);
    }

    /**
     * Get the end of the quarter for a given date.
     */
    private function getQuarterEnd(\DateTimeImmutable $date): \DateTimeImmutable
    {
        $month = (int) $date->format('n');
        $quarterEndMonth = (int) (floor(($month - 1) / 3) * 3 + 3);

        return $date->setDate(
            (int) $date->format('Y'),
            $quarterEndMonth,
            1
        )->modify('last day of this month')->setTime(23, 59, 59);
    }
}
