<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

/**
 * Immutable value object representing a single period's depreciation forecast.
 *
 * This is used by the DepreciationCalculator to forecast depreciation
 * for future periods without creating full schedule periods.
 *
 * @package Nexus\FixedAssetDepreciation\ValueObjects
 */
final readonly class PeriodForecast
{
    /**
     * @param string $periodId The period identifier (e.g., "2024-01")
     * @param float $amount The forecasted depreciation amount for this period
     * @param float $netBookValue The expected net book value after this period
     * @param float $accumulatedDepreciation The accumulated depreciation after this period
     */
    public function __construct(
        public string $periodId,
        public float $amount,
        public float $netBookValue,
        public float $accumulatedDepreciation = 0.0,
    ) {}

    /**
     * Create a new period forecast.
     *
     * @param string $periodId The period identifier
     * @param float $amount The depreciation amount
     * @param float $netBookValue The net book value after depreciation
     * @param float $accumulatedDepreciation The accumulated depreciation
     * @return self
     */
    public static function create(
        string $periodId,
        float $amount,
        float $netBookValue,
        float $accumulatedDepreciation = 0.0
    ): self {
        return new self(
            periodId: $periodId,
            amount: $amount,
            netBookValue: $netBookValue,
            accumulatedDepreciation: $accumulatedDepreciation,
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array{
     *     periodId: string,
     *     amount: float,
     *     netBookValue: float,
     *     accumulatedDepreciation: float
     * }
     */
    public function toArray(): array
    {
        return [
            'periodId' => $this->periodId,
            'amount' => $this->amount,
            'netBookValue' => $this->netBookValue,
            'accumulatedDepreciation' => $this->accumulatedDepreciation,
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
            'PeriodForecast %s: Amount=%.2f, NBV=%.2f, Accumulated=%.2f',
            $this->periodId,
            $this->amount,
            $this->netBookValue,
            $this->accumulatedDepreciation
        );
    }
}
