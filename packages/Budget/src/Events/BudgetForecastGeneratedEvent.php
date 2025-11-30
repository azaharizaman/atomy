<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Forecast Generated Event
 * 
 * Published when a budget forecast is generated.
 */
final readonly class BudgetForecastGeneratedEvent
{
    public function __construct(
        private string $budgetId,
        private string $targetPeriodId,
        private float $predictedAllocation,
        private float $confidenceLower,
        private float $confidenceUpper,
        private float $growthRate,
        private string $currency
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getTargetPeriodId(): string
    {
        return $this->targetPeriodId;
    }

    public function getPredictedAllocation(): float
    {
        return $this->predictedAllocation;
    }

    public function getConfidenceLower(): float
    {
        return $this->confidenceLower;
    }

    public function getConfidenceUpper(): float
    {
        return $this->confidenceUpper;
    }

    public function getGrowthRate(): float
    {
        return $this->growthRate;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
