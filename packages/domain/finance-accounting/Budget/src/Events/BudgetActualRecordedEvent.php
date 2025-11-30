<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Actual Recorded Event
 * 
 * Published when actual spending is recorded against a budget.
 */
final readonly class BudgetActualRecordedEvent
{
    public function __construct(
        private string $budgetId,
        private float $amount,
        private string $currency,
        private string $sourceType,
        private string $sourceId,
        private int $sourceLineNumber,
        private string $accountId,
        private float $newActualTotal,
        private float $availableRemaining,
        private bool $commitmentReleased
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

    public function getNewActualTotal(): float
    {
        return $this->newActualTotal;
    }

    public function getAvailableRemaining(): float
    {
        return $this->availableRemaining;
    }

    public function isCommitmentReleased(): bool
    {
        return $this->commitmentReleased;
    }
}
