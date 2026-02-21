<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;

/**
 * Immutable value object representing a single depreciation schedule period.
 *
 * This represents one period in a depreciation schedule, containing
 * the dates, amounts, and status for that specific period.
 *
 * @package Nexus\FixedAssetDepreciation\ValueObjects
 */
final readonly class DepreciationSchedulePeriod
{
    /**
     * Create a new depreciation schedule period.
     *
     * @param string $scheduleId The schedule this period belongs to
     * @param string $periodId The fiscal period identifier
     * @param int $periodNumber Sequential period number (1-based)
     * @param DateTimeImmutable $periodStartDate Start date of the period
     * @param DateTimeImmutable $periodEndDate End date of the period
     * @param float $openingBookValue Book value at the start of this period
     * @param float $depreciationAmount Depreciation amount for this period
     * @param float $previousAccumulatedDepreciation Total accumulated depreciation before this period
     * @return self New instance
     */
    public static function create(
        string $scheduleId,
        string $periodId,
        int $periodNumber,
        \DateTimeImmutable $periodStartDate,
        \DateTimeImmutable $periodEndDate,
        float $openingBookValue,
        float $depreciationAmount,
        float $previousAccumulatedDepreciation
    ): self {
        $accumulatedDepreciation = $previousAccumulatedDepreciation + $depreciationAmount;
        $bookValueAtPeriodEnd = $openingBookValue - $depreciationAmount;

        return new self(
            id: sprintf('PERIOD-%s-%d', $scheduleId, $periodNumber),
            scheduleId: $scheduleId,
            periodId: $periodId,
            periodNumber: $periodNumber,
            periodStartDate: $periodStartDate,
            periodEndDate: $periodEndDate,
            depreciationAmount: $depreciationAmount,
            accumulatedDepreciation: $accumulatedDepreciation,
            bookValueAtPeriodStart: $openingBookValue,
            bookValueAtPeriodEnd: $bookValueAtPeriodEnd,
            status: DepreciationStatus::CALCULATED,
            depreciationId: null,
            journalEntryId: null,
            calculationDate: null,
            postingDate: null,
        );
    }

    /**
     * @param string $id Unique identifier for this period
     * @param string $scheduleId The schedule this period belongs to
     * @param string $periodId The fiscal period identifier
     * @param int $periodNumber Sequential period number (1-based)
     * @param DateTimeImmutable $periodStartDate Start date of the period
     * @param DateTimeImmutable $periodEndDate End date of the period
     * @param float $depreciationAmount Depreciation amount for this period
     * @param float $accumulatedDepreciation Total accumulated depreciation through this period
     * @param float $bookValueAtPeriodStart Book value at the start of this period
     * @param float $bookValueAtPeriodEnd Book value at the end of this period
     * @param DepreciationStatus $status Current status of this period
     * @param string|null $depreciationId The depreciation record ID if calculated
     * @param string|null $journalEntryId The journal entry ID if posted
     * @param DateTimeImmutable|null $calculationDate Date when depreciation was calculated
     * @param DateTimeImmutable|null $postingDate Date when depreciation was posted
     */
    public function __construct(
        public string $id,
        public string $scheduleId,
        public string $periodId,
        public int $periodNumber,
        public \DateTimeImmutable $periodStartDate,
        public \DateTimeImmutable $periodEndDate,
        public float $depreciationAmount,
        public float $accumulatedDepreciation,
        public float $bookValueAtPeriodStart,
        public float $bookValueAtPeriodEnd,
        public DepreciationStatus $status,
        public ?string $depreciationId = null,
        public ?string $journalEntryId = null,
        public ?\DateTimeImmutable $calculationDate = null,
        public ?\DateTimeImmutable $postingDate = null,
    ) {}

    /**
     * Check if this period has been calculated.
     *
     * @return bool True if depreciation has been calculated
     */
    public function isCalculated(): bool
    {
        return $this->status !== DepreciationStatus::CALCULATED ||
               $this->depreciationId !== null;
    }

    /**
     * Check if this period has been posted.
     *
     * @return bool True if depreciation has been posted to GL
     */
    public function isPosted(): bool
    {
        return $this->status === DepreciationStatus::POSTED;
    }

    /**
     * Check if this period has been reversed.
     *
     * @return bool True if depreciation has been reversed
     */
    public function isReversed(): bool
    {
        return $this->status === DepreciationStatus::REVERSED;
    }

    /**
     * Check if this period has been adjusted.
     *
     * @return bool True if depreciation has been adjusted
     */
    public function isAdjusted(): bool
    {
        return $this->status === DepreciationStatus::ADJUSTED;
    }

    /**
     * Check if this period can be calculated.
     *
     * @return bool True if period is ready for calculation
     */
    public function canBeCalculated(): bool
    {
        return $this->status === DepreciationStatus::CALCULATED && $this->depreciationId === null;
    }

    /**
     * Check if this period can be posted.
     *
     * @return bool True if period can be posted to GL
     */
    public function canBePosted(): bool
    {
        return $this->depreciationId !== null &&
               !$this->isPosted() &&
               !$this->isReversed();
    }

    /**
     * Get the depreciable amount for this period.
     *
     * @return float The depreciable amount
     */
    public function getDepreciableAmount(): float
    {
        return $this->bookValueAtPeriodStart - $this->bookValueAtPeriodEnd + $this->depreciationAmount;
    }

    /**
     * Get the number of days in this period.
     *
     * @return int Number of days
     */
    public function getDaysInPeriod(): int
    {
        return (int) ($this->periodEndDate->getTimestamp() - $this->periodStartDate->getTimestamp()) / 86400 + 1;
    }

    /**
     * Get the depreciation rate for this period.
     *
     * @return float The depreciation rate
     */
    public function getDepreciationRate(): float
    {
        if ($this->bookValueAtPeriodStart === 0.0) {
            return 0.0;
        }
        return $this->depreciationAmount / $this->bookValueAtPeriodStart;
    }

    /**
     * Check if this period is fully depreciated.
     *
     * @return bool True if asset is fully depreciated after this period
     */
    public function isFullyDepreciatedAfter(): bool
    {
        return $this->bookValueAtPeriodEnd <= 0.0;
    }

    /**
     * Create with updated status.
     *
     * @param DepreciationStatus $newStatus The new status
     * @return self New instance with updated status
     */
    public function withStatus(DepreciationStatus $newStatus): self
    {
        return new self(
            id: $this->id,
            scheduleId: $this->scheduleId,
            periodId: $this->periodId,
            periodNumber: $this->periodNumber,
            periodStartDate: $this->periodStartDate,
            periodEndDate: $this->periodEndDate,
            depreciationAmount: $this->depreciationAmount,
            accumulatedDepreciation: $this->accumulatedDepreciation,
            bookValueAtPeriodStart: $this->bookValueAtPeriodStart,
            bookValueAtPeriodEnd: $this->bookValueAtPeriodEnd,
            status: $newStatus,
            depreciationId: $this->depreciationId,
            journalEntryId: $this->journalEntryId,
            calculationDate: $this->calculationDate,
            postingDate: $this->postingDate,
        );
    }

    /**
     * Create with calculation details.
     *
     * @param string $depreciationId The depreciation record ID
     * @param DateTimeImmutable $calculationDate The calculation date
     * @return self New instance with calculation details
     */
    public function withCalculationDetails(string $depreciationId, \DateTimeImmutable $calculationDate): self
    {
        return new self(
            id: $this->id,
            scheduleId: $this->scheduleId,
            periodId: $this->periodId,
            periodNumber: $this->periodNumber,
            periodStartDate: $this->periodStartDate,
            periodEndDate: $this->periodEndDate,
            depreciationAmount: $this->depreciationAmount,
            accumulatedDepreciation: $this->accumulatedDepreciation,
            bookValueAtPeriodStart: $this->bookValueAtPeriodStart,
            bookValueAtPeriodEnd: $this->bookValueAtPeriodEnd,
            status: DepreciationStatus::CALCULATED,
            depreciationId: $depreciationId,
            journalEntryId: $this->journalEntryId,
            calculationDate: $calculationDate,
            postingDate: $this->postingDate,
        );
    }

    /**
     * Create with posting details.
     *
     * @param string $journalEntryId The journal entry ID
     * @param DateTimeImmutable $postingDate The posting date
     * @return self New instance with posting details
     */
    public function withPostingDetails(string $journalEntryId, \DateTimeImmutable $postingDate): self
    {
        return new self(
            id: $this->id,
            scheduleId: $this->scheduleId,
            periodId: $this->periodId,
            periodNumber: $this->periodNumber,
            periodStartDate: $this->periodStartDate,
            periodEndDate: $this->periodEndDate,
            depreciationAmount: $this->depreciationAmount,
            accumulatedDepreciation: $this->accumulatedDepreciation,
            bookValueAtPeriodStart: $this->bookValueAtPeriodStart,
            bookValueAtPeriodEnd: $this->bookValueAtPeriodEnd,
            status: DepreciationStatus::POSTED,
            depreciationId: $this->depreciationId,
            journalEntryId: $journalEntryId,
            calculationDate: $this->calculationDate,
            postingDate: $postingDate,
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array{
     *     id: string,
     *     scheduleId: string,
     *     periodId: string,
     *     periodNumber: int,
     *     periodStartDate: string,
     *     periodEndDate: string,
     *     depreciationAmount: float,
     *     accumulatedDepreciation: float,
     *     bookValueAtPeriodStart: float,
     *     bookValueAtPeriodEnd: float,
     *     status: string,
     *     depreciationId: string|null,
     *     journalEntryId: string|null,
     *     calculationDate: string|null,
     *     postingDate: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'scheduleId' => $this->scheduleId,
            'periodId' => $this->periodId,
            'periodNumber' => $this->periodNumber,
            'periodStartDate' => $this->periodStartDate->format('Y-m-d'),
            'periodEndDate' => $this->periodEndDate->format('Y-m-d'),
            'depreciationAmount' => $this->depreciationAmount,
            'accumulatedDepreciation' => $this->accumulatedDepreciation,
            'bookValueAtPeriodStart' => $this->bookValueAtPeriodStart,
            'bookValueAtPeriodEnd' => $this->bookValueAtPeriodEnd,
            'status' => $this->status->value,
            'depreciationId' => $this->depreciationId,
            'journalEntryId' => $this->journalEntryId,
            'calculationDate' => $this->calculationDate?->format('Y-m-d'),
            'postingDate' => $this->postingDate?->format('Y-m-d'),
        ];
    }

    /**
     * Format as human-readable string.
     *
     * @return string
     */
    public function format(): string
    {
        return sprintf(
            'Period %d: %s - %s | Depreciation: %.2f | Accumulated: %.2f | Status: %s',
            $this->periodNumber,
            $this->periodStartDate->format('Y-m-d'),
            $this->periodEndDate->format('Y-m-d'),
            $this->depreciationAmount,
            $this->accumulatedDepreciation,
            $this->status->value
        );
    }
}
