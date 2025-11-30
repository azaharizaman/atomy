<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Override Requested Event
 * 
 * Published when a budget override approval is requested (for Workflow integration).
 */
final readonly class BudgetOverrideRequestedEvent
{
    public function __construct(
        private string $budgetId,
        private float $requestedAmount,
        private string $currency,
        private string $requestorId,
        private string $reason,
        private string $requiredApprovalLevel,
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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getRequestorId(): string
    {
        return $this->requestorId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getRequiredApprovalLevel(): string
    {
        return $this->requiredApprovalLevel;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
