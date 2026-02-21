<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Entities;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationSchedulePeriod;

/**
 * Entity representing a complete depreciation schedule for an asset.
 *
 * A depreciation schedule contains all periods from acquisition through
 * the end of useful life, tracking depreciation amounts, accumulated
 * depreciation, and book values for each period.
 *
 * @package Nexus\FixedAssetDepreciation\Entities
 *
 * @implements IteratorAggregate<int, DepreciationSchedulePeriod>
 */
final class DepreciationSchedule implements IteratorAggregate, Countable
{
    /**
     * @param string $id Unique identifier for this schedule
     * @param string $assetId The asset this schedule belongs to
     * @param string $tenantId The tenant identifier
     * @param DepreciationMethodType $methodType The depreciation method
     * @param DepreciationType $depreciationType Book or Tax depreciation
     * @param DepreciationLife $depreciationLife The depreciation life parameters
     * @param DateTimeImmutable $acquisitionDate Asset acquisition date
     * @param DateTimeImmutable $startDepreciationDate When depreciation starts
     * @param DateTimeImmutable|null $endDepreciationDate When depreciation ends
     * @param ProrateConvention $prorateConvention The prorate convention
     * @param array $periods The depreciation periods
     * @param DepreciationStatus $status Current status
     * @param string $currency The currency code
     * @param DateTimeImmutable $createdAt When the schedule was created
     * @param DateTimeImmutable|null $updatedAt Last update time
     * @param string|null $closedReason Reason for closing (if closed)
     * @param DateTimeImmutable|null $closedAt When the schedule was closed
     */
    public function __construct(
        public readonly string $id,
        public readonly string $assetId,
        public readonly string $tenantId,
        public readonly DepreciationMethodType $methodType,
        public readonly DepreciationType $depreciationType,
        public readonly DepreciationLife $depreciationLife,
        public readonly \DateTimeImmutable $acquisitionDate,
        public readonly \DateTimeImmutable $startDepreciationDate,
        public readonly ?\DateTimeImmutable $endDepreciationDate,
        public readonly ProrateConvention $prorateConvention,
        public readonly array $periods,
        public readonly DepreciationStatus $status,
        public readonly string $currency,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $updatedAt = null,
        public readonly ?string $closedReason = null,
        public readonly ?\DateTimeImmutable $closedAt = null,
    ) {}

    /**
     * Create a new schedule.
     *
     * @param string $assetId The asset ID
     * @param string $tenantId The tenant ID
     * @param DepreciationMethodType $methodType The depreciation method
     * @param DepreciationType $depreciationType The depreciation type
     * @param DepreciationLife $depreciationLife The depreciation life
     * @param DateTimeImmutable $acquisitionDate The acquisition date
     * @param ProrateConvention $prorateConvention The prorate convention
     * @param string $currency The currency code
     * @param array $periods The schedule periods
     * @return self
     */
    public static function create(
        string $assetId,
        string $tenantId,
        DepreciationMethodType $methodType,
        DepreciationType $depreciationType,
        DepreciationLife $depreciationLife,
        DateTimeImmutable $acquisitionDate,
        ProrateConvention $prorateConvention,
        string $currency = 'USD',
        array $periods = []
    ): self {
        return new self(
            id: uniqid('sch_'),
            assetId: $assetId,
            tenantId: $tenantId,
            methodType: $methodType,
            depreciationType: $depreciationType,
            depreciationLife: $depreciationLife,
            acquisitionDate: $acquisitionDate,
            startDepreciationDate: $acquisitionDate,
            endDepreciationDate: null,
            prorateConvention: $prorateConvention,
            periods: $periods,
            status: DepreciationStatus::CALCULATED,
            currency: $currency,
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
            closedReason: null,
            closedAt: null
        );
    }

    /**
     * Check if the schedule is active.
     *
     * @return bool True if active
     */
    public function isActive(): bool
    {
        return $this->status !== DepreciationStatus::REVERSED;
    }

    /**
     * Check if the schedule is closed.
     *
     * @return bool True if closed
     */
    public function isClosed(): bool
    {
        return $this->closedAt !== null;
    }

    /**
     * Check if the schedule is fully depreciated.
     *
     * @return bool True if fully depreciated
     */
    public function isFullyDepreciated(): bool
    {
        if (empty($this->periods)) {
            return false;
        }

        $lastPeriod = end($this->periods);
        return $lastPeriod instanceof DepreciationSchedulePeriod &&
               $lastPeriod->isFullyDepreciatedAfter();
    }

    /**
     * Get the total depreciation amount.
     *
     * @return float Total depreciation
     */
    public function getTotalDepreciation(): float
    {
        $total = 0.0;
        foreach ($this->periods as $period) {
            $total += $period->depreciationAmount;
        }
        return $total;
    }

    /**
     * Get accumulated depreciation.
     *
     * @param DateTimeImmutable|null $asOfDate Calculate up to this date
     * @return float Accumulated depreciation
     */
    public function getAccumulatedDepreciation(?\DateTimeImmutable $asOfDate = null): float
    {
        $accumulated = 0.0;

        foreach ($this->periods as $period) {
            if ($asOfDate !== null && $period->periodEndDate > $asOfDate) {
                break;
            }
            $accumulated = $period->accumulatedDepreciation;
        }

        return $accumulated;
    }

    /**
     * Get current book value.
     *
     * @param DateTimeImmutable|null $asOfDate Calculate up to this date
     * @return float Current book value
     */
    public function getCurrentBookValue(?\DateTimeImmutable $asOfDate = null): float
    {
        $accumulated = $this->getAccumulatedDepreciation($asOfDate);
        return $this->depreciationLife->totalDepreciableAmount + $this->depreciationLife->salvageValue - $accumulated;
    }

    /**
     * Get remaining depreciation.
     *
     * @param DateTimeImmutable|null $asOfDate Calculate from this date
     * @return float Remaining depreciation
     */
    public function getRemainingDepreciation(?\DateTimeImmutable $asOfDate = null): float
    {
        $accumulated = $this->getAccumulatedDepreciation($asOfDate);
        return max(0, $this->depreciationLife->totalDepreciableAmount - $accumulated);
    }

    /**
     * Get total number of periods.
     *
     * @return int Number of periods
     */
    public function count(): int
    {
        return count($this->periods);
    }

    /**
     * Get iterator for periods.
     *
     * @return ArrayIterator<int, DepreciationSchedulePeriod>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->periods);
    }

    /**
     * Get period by index.
     *
     * @param int $index The period index
     * @return DepreciationSchedulePeriod|null The period
     */
    public function getPeriod(int $index): ?DepreciationSchedulePeriod
    {
        return $this->periods[$index] ?? null;
    }

    /**
     * Get the first period.
     *
     * @return DepreciationSchedulePeriod|null First period
     */
    public function getFirstPeriod(): ?DepreciationSchedulePeriod
    {
        return $this->periods[0] ?? null;
    }

    /**
     * Get the last period.
     *
     * @return DepreciationSchedulePeriod|null Last period
     */
    public function getLastPeriod(): ?DepreciationSchedulePeriod
    {
        $lastIndex = count($this->periods) - 1;
        return $this->periods[$lastIndex] ?? null;
    }

    /**
     * Close the schedule.
     *
     * @param string $reason Reason for closing
     * @return self New instance with closed status
     */
    public function close(string $reason): self
    {
        return new self(
            id: $this->id,
            assetId: $this->assetId,
            methodType: $this->methodType,
            depreciationType: $this->depreciationType,
            depreciationLife: $this->depreciationLife,
            acquisitionDate: $this->acquisitionDate,
            startDepreciationDate: $this->startDepreciationDate,
            endDepreciationDate: new \DateTimeImmutable(),
            prorateConvention: $this->prorateConvention,
            periods: $this->periods,
            status: DepreciationStatus::CALCULATED,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            closedReason: $reason,
            closedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Update periods.
     *
     * @param array $periods New periods
     * @return self New instance with updated periods
     */
    public function withPeriods(array $periods): self
    {
        return new self(
            id: $this->id,
            assetId: $this->assetId,
            methodType: $this->methodType,
            depreciationType: $this->depreciationType,
            depreciationLife: $this->depreciationLife,
            acquisitionDate: $this->acquisitionDate,
            startDepreciationDate: $this->startDepreciationDate,
            endDepreciationDate: $this->endDepreciationDate,
            prorateConvention: $this->prorateConvention,
            periods: $periods,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            closedReason: $this->closedReason,
            closedAt: $this->closedAt,
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assetId' => $this->assetId,
            'methodType' => $this->methodType->value,
            'depreciationType' => $this->depreciationType->value,
            'depreciationLife' => [
                'usefulLifeYears' => $this->depreciationLife->usefulLifeYears,
                'usefulLifeMonths' => $this->depreciationLife->usefulLifeMonths,
                'salvageValue' => $this->depreciationLife->salvageValue,
                'totalDepreciableAmount' => $this->depreciationLife->totalDepreciableAmount,
            ],
            'acquisitionDate' => $this->acquisitionDate->format('Y-m-d'),
            'startDepreciationDate' => $this->startDepreciationDate->format('Y-m-d'),
            'endDepreciationDate' => $this->endDepreciationDate?->format('Y-m-d'),
            'prorateConvention' => $this->prorateConvention->value,
            'status' => $this->status->value,
            'isActive' => $this->isActive(),
            'isClosed' => $this->isClosed(),
            'isFullyDepreciated' => $this->isFullyDepreciated(),
            'totalDepreciation' => $this->getTotalDepreciation(),
            'periodCount' => count($this->periods),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'closedReason' => $this->closedReason,
            'closedAt' => $this->closedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
