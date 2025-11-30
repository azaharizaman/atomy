<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Created Event
 * 
 * Published when a new budget is created.
 */
final readonly class BudgetCreatedEvent
{
    public function __construct(
        private string $budgetId,
        private string $periodId,
        private string $budgetType,
        private float $allocatedAmount,
        private string $currency,
        private ?string $departmentId = null,
        private ?string $projectId = null
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getBudgetType(): string
    {
        return $this->budgetType;
    }

    public function getAllocatedAmount(): float
    {
        return $this->allocatedAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDepartmentId(): ?string
    {
        return $this->departmentId;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }
}
