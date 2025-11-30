<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Exceeded Event
 * 
 * Published when budget limit is exceeded (for Notifier alerts).
 */
final readonly class BudgetExceededEvent
{
    public function __construct(
        private string $budgetId,
        private float $requestedAmount,
        private float $availableAmount,
        private float $shortfall,
        private string $currency,
        private ?string $departmentId = null,
        private ?string $managerId = null,
        private array $context = []
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getRequestedAmount(): float
    {
        return $this->requestedAmount;
    }

    public function getAvailableAmount(): float
    {
        return $this->availableAmount;
    }

    public function getShortfall(): float
    {
        return $this->shortfall;
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

    public function getContext(): array
    {
        return $this->context;
    }
}
