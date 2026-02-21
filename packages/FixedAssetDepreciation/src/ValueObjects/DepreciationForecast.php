<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\ValueObjects\PeriodForecast;

/**
 * Immutable value object representing future depreciation forecast.
 *
 * This value object contains projected depreciation amounts for
 * future periods based on the current schedule and method.
 *
 * @package Nexus\FixedAssetDepreciation\ValueObjects
 *
 * @implements IteratorAggregate<int, DepreciationSchedulePeriod>
 */
final readonly class DepreciationForecast implements IteratorAggregate, Countable
{
    /**
     * @param array<DepreciationSchedulePeriod> $periods The forecasted periods
     * @param float $totalDepreciation Total depreciation over forecast period
     * @param float $averageDepreciation Average depreciation per period
     * @param int $numberOfPeriods Number of periods forecasted
     */
    public function __construct(
        public array $periods,
        public float $totalDepreciation,
        public float $averageDepreciation,
        public int $numberOfPeriods,
    ) {}

    /**
     * Create from asset data.
     *
     * @param string $assetId The asset identifier
     * @param array<DepreciationSchedulePeriod> $periods The forecasted periods
     * @return self
     */
    public static function create(string $assetId, array $periods): self
    {
        $totalDepreciation = 0.0;
        foreach ($periods as $period) {
            $totalDepreciation += $period->depreciationAmount;
        }

        $numberOfPeriods = count($periods);
        $averageDepreciation = $numberOfPeriods > 0
            ? $totalDepreciation / $numberOfPeriods
            : 0.0;

        return new self(
            periods: $periods,
            totalDepreciation: $totalDepreciation,
            averageDepreciation: $averageDepreciation,
            numberOfPeriods: $numberOfPeriods,
        );
    }

    /**
     * Create forecast from period forecasts.
     *
     * Converts PeriodForecast objects into DepreciationSchedulePeriod objects
     * and creates the forecast.
     *
     * @param string $assetId The asset identifier
     * @param array<PeriodForecast> $periodForecasts The forecasted periods
     * @return self
     */
    public static function fromPeriodForecasts(string $assetId, array $periodForecasts): self
    {
        $periods = [];
        $periodNumber = 1;

        foreach ($periodForecasts as $forecast) {
            $periods[] = new DepreciationSchedulePeriod(
                id: sprintf('FORECAST-%s-%d', $assetId, $periodNumber),
                scheduleId: sprintf('SCH-%s', $assetId),
                periodId: $forecast->periodId,
                periodNumber: $periodNumber,
                periodStartDate: new \DateTimeImmutable($forecast->periodId . '-01'),
                periodEndDate: (new \DateTimeImmutable($forecast->periodId . '-01'))->modify('+1 month -1 day'),
                depreciationAmount: $forecast->amount,
                accumulatedDepreciation: $forecast->accumulatedDepreciation,
                bookValueAtPeriodStart: $forecast->netBookValue + $forecast->amount,
                bookValueAtPeriodEnd: $forecast->netBookValue,
                status: DepreciationStatus::CALCULATED,
                depreciationId: null,
                journalEntryId: null,
                calculationDate: null,
                postingDate: null,
            );
            $periodNumber++;
        }

        return self::create($assetId, $periods);
    }

    /**
     * Get total remaining depreciation.
     *
     * @return float
     */
    public function getTotalRemainingDepreciation(): float
    {
        return $this->totalDepreciation;
    }

    /**
     * Get depreciation for a specific period.
     *
     * @param int $periodIndex The period index (0-based)
     * @return DepreciationSchedulePeriod|null The period or null
     */
    public function getPeriod(int $periodIndex): ?DepreciationSchedulePeriod
    {
        return $this->periods[$periodIndex] ?? null;
    }

    /**
     * Get the first period.
     *
     * @return DepreciationSchedulePeriod|null The first period
     */
    public function getFirstPeriod(): ?DepreciationSchedulePeriod
    {
        return $this->periods[0] ?? null;
    }

    /**
     * Get the last period.
     *
     * @return DepreciationSchedulePeriod|null The last period
     */
    public function getLastPeriod(): ?DepreciationSchedulePeriod
    {
        $lastIndex = count($this->periods) - 1;
        return $this->periods[$lastIndex] ?? null;
    }

    /**
     * Check if forecast has periods.
     *
     * @return bool True if there are forecast periods
     */
    public function hasPeriods(): bool
    {
        return count($this->periods) > 0;
    }

    /**
     * Get cumulative depreciation up to a period.
     *
     * @param int $upToPeriodIndex The period index (exclusive)
     * @return float The cumulative depreciation
     */
    public function getCumulativeDepreciation(int $upToPeriodIndex): float
    {
        $cumulative = 0.0;
        for ($i = 0; $i < $upToPeriodIndex && $i < count($this->periods); $i++) {
            $cumulative += $this->periods[$i]->depreciationAmount;
        }
        return $cumulative;
    }

    /**
     * Get year-by-year summary.
     *
     * @return array<int, float> Array keyed by year with total depreciation
     */
    public function getYearlySummary(): array
    {
        $yearly = [];
        foreach ($this->periods as $period) {
            $year = (int) $period->periodStartDate->format('Y');
            if (!isset($yearly[$year])) {
                $yearly[$year] = 0.0;
            }
            $yearly[$year] += $period->depreciationAmount;
        }
        return $yearly;
    }

    /**
     * Get number of periods.
     *
     * @return int The number of periods
     */
    public function count(): int
    {
        return $this->numberOfPeriods;
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
     * Format as string representation.
     *
     * @return string
     */
    public function format(): string
    {
        return sprintf(
            'DepreciationForecast: %d periods, Total: %.2f, Average: %.2f',
            $this->numberOfPeriods,
            $this->totalDepreciation,
            $this->averageDepreciation
        );
    }

    /**
     * Convert to array.
     *
     * @return array{
     *     periods: array<int, array>,
     *     totalDepreciation: float,
     *     averageDepreciation: float,
     *     numberOfPeriods: int
     * }
     */
    public function toArray(): array
    {
        return [
            'periods' => array_map(
                fn(DepreciationSchedulePeriod $p) => $p->toArray(),
                $this->periods
            ),
            'totalDepreciation' => $this->totalDepreciation,
            'averageDepreciation' => $this->averageDepreciation,
            'numberOfPeriods' => $this->numberOfPeriods,
        ];
    }
}
