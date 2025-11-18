<?php

declare(strict_types=1);

namespace Nexus\Period\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Period Date Range Value Object
 * 
 * Immutable representation of a fiscal period's date range with validation.
 */
final readonly class PeriodDateRange
{
    public function __construct(
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate
    ) {
        if ($this->endDate < $this->startDate) {
            throw new InvalidArgumentException(
                "End date must be after start date. Got start: {$this->startDate->format('Y-m-d')}, end: {$this->endDate->format('Y-m-d')}"
            );
        }
    }

    /**
     * Create from date strings (Y-m-d format)
     */
    public static function fromStrings(string $startDate, string $endDate): self
    {
        return new self(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate)
        );
    }

    /**
     * Create a monthly date range
     */
    public static function forMonth(int $year, int $month): self
    {
        $startDate = new DateTimeImmutable("{$year}-{$month}-01");
        $endDate = $startDate->modify('last day of this month');
        
        return new self($startDate, $endDate);
    }

    /**
     * Create a quarterly date range
     */
    public static function forQuarter(int $year, int $quarter): self
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidArgumentException("Quarter must be between 1 and 4, got: {$quarter}");
        }

        $startMonth = ($quarter - 1) * 3 + 1;
        $startDate = new DateTimeImmutable("{$year}-{$startMonth}-01");
        $endDate = $startDate->modify('+2 months')->modify('last day of this month');
        
        return new self($startDate, $endDate);
    }

    /**
     * Create a yearly date range
     */
    public static function forYear(int $year): self
    {
        return new self(
            new DateTimeImmutable("{$year}-01-01"),
            new DateTimeImmutable("{$year}-12-31")
        );
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
     * Check if a date falls within this range (inclusive)
     */
    public function containsDate(DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Check if this range overlaps with another range
     */
    public function overlaps(self $other): bool
    {
        return $this->startDate <= $other->endDate && $this->endDate >= $other->startDate;
    }

    /**
     * Get the number of days in this range
     */
    public function getDayCount(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days + 1;
    }

    /**
     * Check if this is a single day range
     */
    public function isSingleDay(): bool
    {
        return $this->startDate->format('Y-m-d') === $this->endDate->format('Y-m-d');
    }

    public function __toString(): string
    {
        return sprintf(
            '%s to %s',
            $this->startDate->format('Y-m-d'),
            $this->endDate->format('Y-m-d')
        );
    }

    /**
     * Check equality with another range
     */
    public function equals(self $other): bool
    {
        return $this->startDate == $other->startDate && $this->endDate == $other->endDate;
    }
}
