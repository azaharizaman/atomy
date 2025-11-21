<?php

declare(strict_types=1);

namespace Nexus\Assets\ValueObjects;

use DateTimeInterface;
use JsonSerializable;

/**
 * Depreciation Schedule Value Object
 *
 * Immutable object representing a single period in depreciation schedule.
 */
final readonly class DepreciationSchedule implements JsonSerializable
{
    public function __construct(
        public int $period,
        public DateTimeInterface $periodStartDate,
        public DateTimeInterface $periodEndDate,
        public float $openingBookValue,
        public float $depreciationAmount,
        public float $accumulatedDepreciation,
        public float $closingBookValue
    ) {
        $this->validate();
    }

    /**
     * Validate schedule data
     */
    private function validate(): void
    {
        if ($this->period < 1) {
            throw new \InvalidArgumentException('Period must be >= 1');
        }

        if ($this->periodEndDate <= $this->periodStartDate) {
            throw new \InvalidArgumentException('Period end date must be after start date');
        }

        if ($this->openingBookValue < 0 || $this->depreciationAmount < 0 || $this->closingBookValue < 0) {
            throw new \InvalidArgumentException('Values cannot be negative');
        }

        // Verify calculation
        $expectedClosing = $this->openingBookValue - $this->depreciationAmount;
        if (abs($expectedClosing - $this->closingBookValue) > 0.01) {
            throw new \InvalidArgumentException('Closing book value calculation mismatch');
        }
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'period_start_date' => $this->periodStartDate->format('Y-m-d'),
            'period_end_date' => $this->periodEndDate->format('Y-m-d'),
            'opening_book_value' => $this->openingBookValue,
            'depreciation_amount' => $this->depreciationAmount,
            'accumulated_depreciation' => $this->accumulatedDepreciation,
            'closing_book_value' => $this->closingBookValue,
        ];
    }

    /**
     * JSON serialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
