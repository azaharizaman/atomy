<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\ValueObjects;

/**
 * Variance between forecast and actual.
 */
final readonly class ForecastVariance
{
    public function __construct(
        private string $periodId,
        private float $forecast,
        private float $actual,
        private float $varianceAmount,
        private float $forecastAccuracy,
    ) {}

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getForecast(): float
    {
        return $this->forecast;
    }

    public function getActual(): float
    {
        return $this->actual;
    }

    public function getVarianceAmount(): float
    {
        return $this->varianceAmount;
    }

    public function getForecastAccuracy(): float
    {
        return $this->forecastAccuracy;
    }
}
