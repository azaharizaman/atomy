<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Utilization Alert Event
 * 
 * Published when budget utilization crosses a configured threshold.
 */
final readonly class BudgetUtilizationAlertEvent
{
    public function __construct(
        private string $budgetId,
        private float $utilizationPercentage,
        private float $threshold,
        private string $severity,
        private string $message,
        private ?string $departmentId = null,
        private ?string $managerId = null
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getUtilizationPercentage(): float
    {
        return $this->utilizationPercentage;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getMessage(): string
    {
        return $this->message;
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
