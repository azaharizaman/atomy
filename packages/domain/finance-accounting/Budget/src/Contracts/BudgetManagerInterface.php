<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Budget\ValueObjects\BudgetVariance;
use Nexus\Budget\ValueObjects\BudgetAvailabilityResult;
use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Manager service contract
 * 
 * Main service for budget management operations including allocation,
 * commitment tracking, actual recording, and variance calculation.
 */
interface BudgetManagerInterface
{
    /**
     * Create a new budget
     * 
     * @param array<string, mixed> $data Budget data
     * @return BudgetInterface
     * @throws \Nexus\Budget\Exceptions\JustificationRequiredException
     * @throws \Nexus\Budget\Exceptions\PeriodClosedException
     * @throws \Nexus\Budget\Exceptions\HierarchyDepthExceededException
     */
    public function createBudget(array $data): BudgetInterface;

    /**
     * Allocate budget amount
     * 
     * @param string $budgetId Budget identifier
     * @param Money $amount Allocation amount
     * @return void
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     * @throws \Nexus\Budget\Exceptions\InvalidBudgetStatusException
     */
    public function allocate(string $budgetId, Money $amount): void;

    /**
     * Commit budget amount (encumbrance)
     * 
     * @param string $budgetId Budget identifier
     * @param Money $amount Commitment amount
     * @param string $accountId GL account identifier
     * @param string $sourceType Source type (e.g., 'purchase_order_line')
     * @param string $sourceId Source identifier
     * @param int $sourceLineNumber Source line number
     * @param string|null $costCenterId Cost center identifier
     * @param string|null $workflowApprovalId Workflow approval identifier (for overrides)
     * @return void
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     * @throws \Nexus\Budget\Exceptions\BudgetExceededException
     * @throws \Nexus\Budget\Exceptions\InvalidBudgetStatusException
     */
    public function commitAmount(
        string $budgetId,
        Money $amount,
        string $accountId,
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber,
        ?string $costCenterId = null,
        ?string $workflowApprovalId = null
    ): void;

    /**
     * Release committed amount
     * 
     * @param string $budgetId Budget identifier
     * @param Money $amount Amount to release
     * @param string $sourceType Source type
     * @param string $sourceId Source identifier
     * @param int $sourceLineNumber Source line number
     * @return void
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     */
    public function releaseCommitment(
        string $budgetId,
        Money $amount,
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber
    ): void;

    /**
     * Record actual spending
     * 
     * @param string $budgetId Budget identifier
     * @param Money $amount Actual amount
     * @param string $accountId GL account identifier
     * @param string $sourceType Source type (e.g., 'journal_entry_line')
     * @param string $sourceId Source identifier
     * @param int $sourceLineNumber Source line number
     * @return void
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     */
    public function recordActual(
        string $budgetId,
        Money $amount,
        string $accountId,
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber
    ): void;

    /**
     * Calculate budget variance
     * 
     * @param string $budgetId Budget identifier
     * @return BudgetVariance
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     */
    public function calculateVariance(string $budgetId): BudgetVariance;

    /**
     * Check budget availability
     * 
     * @param string $budgetId Budget identifier
     * @param Money $requestedAmount Requested amount
     * @return BudgetAvailabilityResult
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     */
    public function checkAvailability(string $budgetId, Money $requestedAmount): BudgetAvailabilityResult;

    /**
     * Lock budget (prevent further modifications)
     * 
     * @param string $budgetId Budget identifier
     * @return void
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     * @throws \Nexus\Budget\Exceptions\InvalidBudgetStatusException
     */
    public function lockBudget(string $budgetId): void;

    /**
     * Transfer allocation between budgets
     * 
     * @param string $fromBudgetId Source budget identifier
     * @param string $toBudgetId Target budget identifier
     * @param Money $amount Transfer amount
     * @param string $reason Transfer reason
     * @return void
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     * @throws \Nexus\Budget\Exceptions\InsufficientBudgetForTransferException
     */
    public function transferAllocation(
        string $fromBudgetId,
        string $toBudgetId,
        Money $amount,
        string $reason
    ): void;

    /**
     * Amend budget allocation
     * 
     * @param string $budgetId Budget identifier
     * @param Money $newAmount New allocation amount
     * @param string $reason Amendment reason
     * @return void
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     * @throws \Nexus\Budget\Exceptions\InvalidBudgetStatusException
     */
    public function amendBudget(string $budgetId, Money $newAmount, string $reason): void;

    /**
     * Create budget simulation
     * 
     * @param string $baseBudgetId Base budget to copy from
     * @param array<string, mixed> $modifications Scenario modifications
     * @return BudgetInterface
     * @throws \Nexus\Budget\Exceptions\BudgetNotFoundException
     */
    public function createSimulation(string $baseBudgetId, array $modifications): BudgetInterface;
}
