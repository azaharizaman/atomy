<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Variance Threshold Exceeded Event
 * 
 * Published when variance exceeds investigation threshold.
 */
final readonly class BudgetVarianceThresholdExceededEvent
{
    public function __construct(
        private string $budgetId,
        private float $variancePercentage,
        private float $threshold,
        private float $allocatedAmount,
        private float $actualAmount,
        private string $currency,
        private ?string $departmentId = null,
        private ?string $managerId = null
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getVariancePercentage(): float
    {
        return $this->variancePercentage;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function getAllocatedAmount(): float
    {
        return $this->allocatedAmount;
    }

    public function getActualAmount(): float
    {
        return $this->actualAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDepartmentId(): ?string
    {
        return $this->departmentId;
    }

    public function getManagerId(): ?string
    {
        return $this->managerId;
    }
}
