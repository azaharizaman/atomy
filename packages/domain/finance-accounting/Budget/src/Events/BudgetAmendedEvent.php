<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

/**
 * Budget Amended Event
 * 
 * Published when a budget allocation is amended.
 */
final readonly class BudgetAmendedEvent
{
    public function __construct(
        private string $budgetId,
        private float $previousAmount,
        private float $newAmount,
        private string $currency,
        private string $reason,
        private int $revisionNumber,
        private ?string $approvedBy = null
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getPreviousAmount(): float
    {
        return $this->previousAmount;
    }

    public function getNewAmount(): float
    {
        return $this->newAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getRevisionNumber(): int
    {
        return $this->revisionNumber;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }
}
