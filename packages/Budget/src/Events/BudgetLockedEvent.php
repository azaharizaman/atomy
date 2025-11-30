<?php

declare(strict_types=1);

namespace Nexus\Budget\Events;

use DateTimeImmutable;

/**
 * Budget Locked Event
 * 
 * Published when a budget is locked (typically at period close).
 */
final readonly class BudgetLockedEvent
{
    public function __construct(
        private string $budgetId,
        private string $periodId,
        private DateTimeImmutable $lockedAt,
        private string $lockedBy,
        private string $reason
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getLockedAt(): DateTimeImmutable
    {
        return $this->lockedAt;
    }

    public function getLockedBy(): string
    {
        return $this->lockedBy;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
