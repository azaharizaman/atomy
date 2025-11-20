<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Consolidation value object
 * 
 * Immutable representation of consolidated budget across hierarchy.
 */
final readonly class BudgetConsolidation
{
    /**
     * @param string $departmentId Parent department identifier
     * @param array<string> $childBudgetIds Child budget identifiers
     * @param Money $totalAllocated Total allocated amount
     * @param Money $totalCommitted Total committed amount
     * @param Money $totalActual Total actual amount
     * @param Money $totalAvailable Total available amount
     */
    public function __construct(
        private string $departmentId,
        private array $childBudgetIds,
        private Money $totalAllocated,
        private Money $totalCommitted,
        private Money $totalActual,
        private Money $totalAvailable
    ) {}

    public function getDepartmentId(): string
    {
        return $this->departmentId;
    }

    /**
     * @return array<string>
     */
    public function getChildBudgetIds(): array
    {
        return $this->childBudgetIds;
    }

    public function getTotalAllocated(): Money
    {
        return $this->totalAllocated;
    }

    public function getTotalCommitted(): Money
    {
        return $this->totalCommitted;
    }

    public function getTotalActual(): Money
    {
        return $this->totalActual;
    }

    public function getTotalAvailable(): Money
    {
        return $this->totalAvailable;
    }

    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->totalAllocated->getAmount() == 0) {
            return 0.0;
        }

        return ($this->totalActual->getAmount() / $this->totalAllocated->getAmount()) * 100;
    }

    /**
     * Get commitment percentage
     */
    public function getCommitmentPercentage(): float
    {
        if ($this->totalAllocated->getAmount() == 0) {
            return 0.0;
        }

        return ($this->totalCommitted->getAmount() / $this->totalAllocated->getAmount()) * 100;
    }

    /**
     * Get number of child budgets
     */
    public function getChildCount(): int
    {
        return count($this->childBudgetIds);
    }
}
