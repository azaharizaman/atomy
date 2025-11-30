<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

use DateTimeImmutable;

/**
 * Budget Approved Event
 * 
 * Published when a budget is approved.
 */
final readonly class BudgetApprovedEvent
{
    public function __construct(
        private string $budgetId,
        private string $approvedBy,
        private DateTimeImmutable $approvedAt,
        private string $approvalLevel
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getApprovedBy(): string
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getApprovalLevel(): string
    {
        return $this->approvalLevel;
    }
}
