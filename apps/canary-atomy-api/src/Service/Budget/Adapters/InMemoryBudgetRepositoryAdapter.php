<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Contracts\BudgetQueryInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Common\ValueObjects\Money;

/**
 * In-Memory Budget Repository Adapter
 * 
 * This adapter is intentionally non-persistent and primarily used for canary-only reads
 * where it returns defaults for all operations. It provides a lightweight implementation 
 * of BudgetRepositoryInterface and BudgetQueryInterface without a database backend.
 * 
 * Default Return Semantics:
 * - findById: returns null
 * - findByPeriod: returns []
 * - findByDepartment: returns null
 * - findByAccountAndPeriod: returns null
 * - findByCostCenterAndPeriod: returns null
 * - findByDepartmentHierarchy: returns []
 * - findByParent: returns []
 * - findDescendants: returns []
 * - getHierarchyDepth: returns 0
 */
final class InMemoryBudgetRepositoryAdapter implements BudgetRepositoryInterface, BudgetQueryInterface
{
    private const string NOT_IMPLEMENTED_MESSAGE = 'BudgetRepository adapter is not fully implemented for canary runtime.';

    public function findById(string $id): ?BudgetInterface
    {
        return null;
    }

    public function findByPeriod(string $periodId): array
    {
        return [];
    }

    public function findByDepartment(string $departmentId, string $periodId): ?BudgetInterface
    {
        return null;
    }

    public function findByAccountAndPeriod(string $accountId, string $periodId): ?BudgetInterface
    {
        return null;
    }

    public function findByCostCenterAndPeriod(string $costCenterId, string $periodId): ?BudgetInterface
    {
        return null;
    }

    public function findByDepartmentHierarchy(string $departmentId, string $periodId): array
    {
        return [];
    }

    public function findByParent(string $parentBudgetId): array
    {
        return [];
    }

    public function findDescendants(string $budgetId): array
    {
        return [];
    }

    public function getHierarchyDepth(string $budgetId): int
    {
        return 0;
    }

    public function create(array $data): BudgetInterface
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }

    public function update(string $id, array $data): void
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }

    public function updateStatus(string $id, string|\BackedEnum $status): void
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }

    public function updateAllocated(string $id, Money $amount): void
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }

    public function updateCommitted(string $id, Money $amount): void
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }

    public function updateActual(string $id, Money $amount): void
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }

    public function delete(string $id): void
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }

    public function createSimulation(string $baseBudgetId, array $modifications = []): BudgetInterface
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED_MESSAGE);
    }
}
