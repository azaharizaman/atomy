<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Committed Event
 * 
 * Published when budget funds are committed (encumbered).
 * Includes current utilization for alert checking.
 */
final readonly class BudgetCommittedEvent
{
    public function __construct(
        private string $budgetId,
        private float $amount,
        private string $currency,
        private string $sourceType,
        private string $sourceId,
        private int $sourceLineNumber,
        private string $accountId,
        private float $currentUtilizationPercentage,
        private float $newCommittedTotal,
        private float $availableRemaining
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function getSourceLineNumber(): int
    {
        return $this->sourceLineNumber;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getCurrentUtilizationPercentage(): float
    {
        return $this->currentUtilizationPercentage;
    }

    public function getNewCommittedTotal(): float
    {
        return $this->newCommittedTotal;
    }

    public function getAvailableRemaining(): float
    {
        return $this->availableRemaining;
    }
}
