<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Statement Period Value Object
 *
 * Immutable representation of a bank statement period with overlap detection.
 */
final readonly class StatementPeriod
{
    public function __construct(
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate
    ) {
        $this->validate();
    }

    /**
     * Validate period dates
     */
    private function validate(): void
    {
        if ($this->endDate < $this->startDate) {
            throw new InvalidArgumentException('End date must be after start date');
        }

        // Ensure reasonable statement period (max 1 year)
        $maxPeriod = $this->startDate->modify('+1 year');
        if ($this->endDate > $maxPeriod) {
            throw new InvalidArgumentException('Statement period cannot exceed 1 year');
        }
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
     * Get number of days in period
     */
    public function getDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->format('%a') + 1;
    }

    /**
     * Check if date falls within period
     */
    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Check if this period overlaps with another
     */
    public function overlaps(self $other): bool
    {
        return $this->startDate <= $other->endDate && $this->endDate >= $other->startDate;
    }

    /**
     * Check if periods are adjacent (end to end with no gap)
     */
    public function isAdjacentTo(self $other): bool
    {
        $nextDay = $this->endDate->modify('+1 day');
        $prevDay = $this->startDate->modify('-1 day');
        
        return $nextDay->format('Y-m-d') === $other->startDate->format('Y-m-d') || 
               $prevDay->format('Y-m-d') === $other->endDate->format('Y-m-d');
    }

    /**
     * Get formatted period string
     */
    public function toString(): string
    {
        return sprintf(
            '%s to %s',
            $this->startDate->format('Y-m-d'),
            $this->endDate->format('Y-m-d')
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
