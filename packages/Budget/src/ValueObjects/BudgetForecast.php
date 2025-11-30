<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Forecast value object
 * 
 * Immutable representation of a budget forecast.
 */
final readonly class BudgetForecast
{
    /**
     * @param string $periodId Target period identifier
     * @param Money $predictedAllocation Predicted allocation amount
     * @param Money $confidenceIntervalLower Lower confidence bound
     * @param Money $confidenceIntervalUpper Upper confidence bound
     * @param float $growthRate Growth rate percentage
     * @param array<string, mixed> $assumptions Forecast assumptions
     */
    public function __construct(
        private string $periodId,
        private Money $predictedAllocation,
        private Money $confidenceIntervalLower,
        private Money $confidenceIntervalUpper,
        private float $growthRate,
        private array $assumptions
    ) {}

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getPredictedAllocation(): Money
    {
        return $this->predictedAllocation;
    }

    public function getConfidenceIntervalLower(): Money
    {
        return $this->confidenceIntervalLower;
    }

    public function getConfidenceIntervalUpper(): Money
    {
        return $this->confidenceIntervalUpper;
    }

    public function getGrowthRate(): float
    {
        return $this->growthRate;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAssumptions(): array
    {
        return $this->assumptions;
    }

    /**
     * Get confidence interval width
     */
    public function getConfidenceWidth(): Money
    {
        return $this->confidenceIntervalUpper->subtract($this->confidenceIntervalLower);
    }

    /**
     * Get forecast certainty score (0-100)
     */
    public function getCertaintyScore(): float
    {
        $width = $this->getConfidenceWidth()->getAmount();
        $predicted = $this->predictedAllocation->getAmount();

        if ($predicted == 0) {
            return 0.0;
        }

        // Narrower interval = higher certainty
        $relativeWidth = ($width / $predicted) * 100;
        return max(0, min(100, 100 - $relativeWidth));
    }
}
