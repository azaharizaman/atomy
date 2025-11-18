<?php

declare(strict_types=1);

namespace Nexus\Period\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Fiscal Year Value Object
 * 
 * Immutable representation of a fiscal year with its date boundaries.
 */
final readonly class FiscalYear
{
    public function __construct(
        private string $year,
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate
    ) {
        if ($this->endDate < $this->startDate) {
            throw new InvalidArgumentException(
                "Fiscal year end date must be after start date"
            );
        }

        if (!preg_match('/^\d{4}$/', $this->year)) {
            throw new InvalidArgumentException(
                "Fiscal year must be in YYYY format, got: {$this->year}"
            );
        }

        // Fiscal year should be approximately 12 months
        $months = $this->startDate->diff($this->endDate)->m + ($this->startDate->diff($this->endDate)->y * 12);
        if ($months < 11 || $months > 13) {
            throw new InvalidArgumentException(
                "Fiscal year must be approximately 12 months, got {$months} months"
            );
        }
    }

    /**
     * Create a calendar fiscal year (Jan 1 - Dec 31)
     */
    public static function calendar(int $year): self
    {
        return new self(
            (string) $year,
            new DateTimeImmutable("{$year}-01-01"),
            new DateTimeImmutable("{$year}-12-31")
        );
    }

    /**
     * Create a fiscal year starting from a specific month
     * 
     * @param int $year The year number
     * @param int $startMonth The starting month (1-12)
     */
    public static function fromStartMonth(int $year, int $startMonth): self
    {
        if ($startMonth < 1 || $startMonth > 12) {
            throw new InvalidArgumentException("Start month must be between 1 and 12, got: {$startMonth}");
        }

        $startDate = new DateTimeImmutable("{$year}-{$startMonth}-01");
        $endDate = $startDate->modify('+11 months')->modify('last day of this month');
        
        return new self((string) $year, $startDate, $endDate);
    }

    public function getYear(): string
    {
        return $this->year;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * Check if a date falls within this fiscal year
     */
    public function containsDate(DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Check if this is a calendar year (Jan-Dec)
     */
    public function isCalendarYear(): bool
    {
        return $this->startDate->format('m-d') === '01-01' 
            && $this->endDate->format('m-d') === '12-31';
    }

    /**
     * Get next fiscal year
     */
    public function next(): self
    {
        $nextYear = (string) ((int) $this->year + 1);
        $nextStart = $this->startDate->modify('+1 year');
        $nextEnd = $this->endDate->modify('+1 year');
        
        return new self($nextYear, $nextStart, $nextEnd);
    }

    /**
     * Get previous fiscal year
     */
    public function previous(): self
    {
        $prevYear = (string) ((int) $this->year - 1);
        $prevStart = $this->startDate->modify('-1 year');
        $prevEnd = $this->endDate->modify('-1 year');
        
        return new self($prevYear, $prevStart, $prevEnd);
    }

    public function __toString(): string
    {
        return "FY{$this->year}";
    }

    public function equals(self $other): bool
    {
        return $this->year === $other->year
            && $this->startDate == $other->startDate
            && $this->endDate == $other->endDate;
    }
}
