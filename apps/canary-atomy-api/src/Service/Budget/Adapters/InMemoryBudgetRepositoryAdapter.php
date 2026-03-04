<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Common\ValueObjects\Money;

final class InMemoryBudgetRepositoryAdapter implements BudgetRepositoryInterface
{
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
        throw new \RuntimeException('BudgetRepository adapter is not fully implemented for canary runtime.');
    }

    public function update(string $id, array $data): void
    {
    }

    public function updateStatus(string $id, string|\BackedEnum $status): void
    {
        throw new \RuntimeException('BudgetRepository adapter is not fully implemented for canary runtime.');
    }

    public function updateAllocated(string $id, Money $amount): void
    {
    }

    public function updateCommitted(string $id, Money $amount): void
    {
    }

    public function updateActual(string $id, Money $amount): void
    {
    }

    public function delete(string $id): void
    {
    }

    public function createSimulation(string $baseBudgetId, array $modifications = []): BudgetInterface
    {
        throw new \RuntimeException('BudgetRepository adapter is not fully implemented for canary runtime.');
    }
}
