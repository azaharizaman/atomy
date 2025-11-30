<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use DateTimeImmutable;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\BudgetingMethodology;
use Nexus\Budget\Enums\RolloverPolicy;
use Nexus\Budget\Enums\VarianceInvestigationStatus;
use Nexus\Budget\Enums\ApprovalLevel;
use Nexus\Finance\ValueObjects\Money;

/**
 * Budget entity contract
 * 
 * Represents a financial budget allocation for a specific period and organizational scope.
 * Supports dual-currency tracking, hierarchical budgeting, and multiple budget types.
 */
interface BudgetInterface
{
    /**
     * Get unique budget identifier
     */
    public function getId(): string;

    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;

    /**
     * Get fiscal period identifier
     */
    public function getPeriodId(): string;

    /**
     * Get parent budget identifier (for hierarchical budgets)
     */
    public function getParentBudgetId(): ?string;

    /**
     * Get hierarchy level (0 = root, 1 = first level child, etc.)
     */
    public function getHierarchyLevel(): int;

    /**
     * Get fiscal year
     */
    public function getFiscalYear(): string;

    /**
     * Get budget type
     */
    public function getBudgetType(): BudgetType;

    /**
     * Get budget status
     */
    public function getStatus(): BudgetStatus;

    /**
     * Get rollover policy
     */
    public function getRolloverPolicy(): RolloverPolicy;

    /**
     * Get budgeting methodology
     */
    public function getBudgetingMethodology(): BudgetingMethodology;

    /**
     * Get spending threshold that triggers workflow approval
     */
    public function getSpendingThreshold(): ?Money;

    /**
     * Check if this is a revenue budget
     */
    public function isRevenueBudget(): bool;

    /**
     * Check if this is a simulation budget
     */
    public function isSimulation(): bool;

    /**
     * Get simulation expiration date
     */
    public function getSimulationExpiresAt(): ?DateTimeImmutable;

    /**
     * Get department identifier
     */
    public function getDepartmentId(): ?string;

    /**
     * Get cost center identifier
     */
    public function getCostCenterId(): ?string;

    /**
     * Get project identifier
     */
    public function getProjectId(): ?string;

    /**
     * Get GL account identifier
     */
    public function getAccountId(): ?string;

    /**
     * Get allocated amount (reporting currency)
     */
    public function getAllocatedAmount(): Money;

    /**
     * Get committed amount (reporting currency)
     */
    public function getCommittedAmount(): Money;

    /**
     * Get actual amount (reporting currency)
     */
    public function getActualAmount(): Money;

    /**
     * Get available amount (reporting currency)
     * Formula: allocated - committed - actual
     */
    public function getAvailableAmount(): Money;

    /**
     * Get functional currency code
     */
    public function getFunctionalCurrency(): string;

    /**
     * Get allocated amount in functional currency
     */
    public function getFunctionalAllocatedAmount(): Money;

    /**
     * Get committed amount in functional currency
     */
    public function getFunctionalCommittedAmount(): Money;

    /**
     * Get actual amount in functional currency
     */
    public function getFunctionalActualAmount(): Money;

    /**
     * Get available amount in functional currency
     */
    public function getFunctionalAvailableAmount(): Money;

    /**
     * Get exchange rate used at allocation time
     */
    public function getAllocatedExchangeRate(): float;

    /**
     * Get variance (allocated - actual)
     */
    public function getVariance(): Money;

    /**
     * Get variance percentage
     */
    public function getVariancePercentage(): float;

    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentage(): float;

    /**
     * Get approved by user identifier
     */
    public function getApprovedBy(): ?string;

    /**
     * Get approval timestamp
     */
    public function getApprovedAt(): ?DateTimeImmutable;

    /**
     * Get approval level
     */
    public function getApprovalLevel(): ?ApprovalLevel;

    /**
     * Get locked timestamp
     */
    public function getLockedAt(): ?DateTimeImmutable;

    /**
     * Get locked by user identifier
     */
    public function getLockedBy(): ?string;

    /**
     * Get justification (required for Zero-Based Budgeting)
     */
    public function getJustification(): ?string;

    /**
     * Get variance investigation status
     */
    public function getVarianceInvestigationStatus(): ?VarianceInvestigationStatus;

    /**
     * Get investigation requested timestamp
     */
    public function getInvestigationRequestedAt(): ?DateTimeImmutable;

    /**
     * Get investigation completed timestamp
     */
    public function getInvestigationCompletedAt(): ?DateTimeImmutable;

    /**
     * Get metadata (investigation responses, transfer history, etc.)
     */
    public function getMetadata(): array;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get last update timestamp
     */
    public function getUpdatedAt(): DateTimeImmutable;
}
