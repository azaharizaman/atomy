<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

/**
 * Budget Repository contract
 * 
 * Provides data access methods for budget entities with tenant scoping.
 */
interface BudgetRepositoryInterface
{
    /**
     * Find budget by identifier
     * 
     * @param string $id Budget identifier
     * @return BudgetInterface|null
     */
    public function findById(string $id): ?BudgetInterface;

    /**
     * Find budgets by period
     * 
     * @param string $periodId Period identifier
     * @return array<BudgetInterface>
     */
    public function findByPeriod(string $periodId): array;

    /**
     * Find budget by department and period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return BudgetInterface|null
     */
    public function findByDepartment(string $departmentId, string $periodId): ?BudgetInterface;

    /**
     * Find budget by account and period
     * 
     * @param string $accountId Account identifier
     * @param string $periodId Period identifier
     * @return BudgetInterface|null
     */
    public function findByAccountAndPeriod(string $accountId, string $periodId): ?BudgetInterface;

    /**
     * Find budgets in department hierarchy
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return array<BudgetInterface>
     */
    public function findByDepartmentHierarchy(string $departmentId, string $periodId): array;

    /**
     * Find budgets by parent budget
     * 
     * @param string $parentBudgetId Parent budget identifier
     * @return array<BudgetInterface>
     */
    public function findByParent(string $parentBudgetId): array;

    /**
     * Find all descendants of a budget (recursive)
     * 
     * @param string $budgetId Budget identifier
     * @return array<BudgetInterface>
     */
    public function findDescendants(string $budgetId): array;

    /**
     * Get hierarchy depth for a budget
     * 
     * @param string $budgetId Budget identifier
     * @return int
     */
    public function getHierarchyDepth(string $budgetId): int;

    /**
     * Create new budget
     * 
     * @param array<string, mixed> $data Budget data
     * @return BudgetInterface
     */
    public function create(array $data): BudgetInterface;

    /**
     * Update budget
     * 
     * @param string $id Budget identifier
     * @param array<string, mixed> $data Budget data
     * @return void
     */
    public function update(string $id, array $data): void;

    /**
     * Update allocated amount
     * 
     * @param string $id Budget identifier
     * @param \Nexus\Uom\ValueObjects\Money $amount Allocated amount
     * @return void
     */
    public function updateAllocated(string $id, \Nexus\Uom\ValueObjects\Money $amount): void;

    /**
     * Update committed amount
     * 
     * @param string $id Budget identifier
     * @param \Nexus\Uom\ValueObjects\Money $amount Committed amount
     * @return void
     */
    public function updateCommitted(string $id, \Nexus\Uom\ValueObjects\Money $amount): void;

    /**
     * Update actual amount
     * 
     * @param string $id Budget identifier
     * @param \Nexus\Uom\ValueObjects\Money $amount Actual amount
     * @return void
     */
    public function updateActual(string $id, \Nexus\Uom\ValueObjects\Money $amount): void;

    /**
     * Delete budget
     * 
     * @param string $id Budget identifier
     * @return void
     */
    public function delete(string $id): void;
}
