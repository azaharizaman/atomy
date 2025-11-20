<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Allocation value object
 * 
 * Immutable representation of a budget allocation.
 */
final readonly class BudgetAllocation
{
    public function __construct(
        private string $periodId,
        private Money $amount,
        private ?string $departmentId = null,
        private ?string $projectId = null,
        private ?string $accountId = null,
        private ?string $costCenterId = null
    ) {}

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDepartmentId(): ?string
    {
        return $this->departmentId;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    public function getCostCenterId(): ?string
    {
        return $this->costCenterId;
    }

    /**
     * Get allocation scope description
     */
    public function getScopeDescription(): string
    {
        $scopes = [];

        if ($this->departmentId) {
            $scopes[] = "Department: {$this->departmentId}";
        }
        if ($this->projectId) {
            $scopes[] = "Project: {$this->projectId}";
        }
        if ($this->accountId) {
            $scopes[] = "Account: {$this->accountId}";
        }
        if ($this->costCenterId) {
            $scopes[] = "Cost Center: {$this->costCenterId}";
        }

        return empty($scopes) ? 'General Allocation' : implode(', ', $scopes);
    }
}
