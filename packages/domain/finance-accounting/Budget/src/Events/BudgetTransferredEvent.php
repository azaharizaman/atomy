<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Transferred Event
 * 
 * Published when budget allocation is transferred between budgets.
 */
final readonly class BudgetTransferredEvent
{
    public function __construct(
        private string $fromBudgetId,
        private string $toBudgetId,
        private float $amount,
        private string $currency,
        private string $reason,
        private ?string $workflowApprovalId = null
    ) {}

    public function getFromBudgetId(): string
    {
        return $this->fromBudgetId;
    }

    public function getToBudgetId(): string
    {
        return $this->toBudgetId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getWorkflowApprovalId(): ?string
    {
        return $this->workflowApprovalId;
    }
}
