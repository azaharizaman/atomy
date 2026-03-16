<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetQueryInterface;

/**
 * In-memory app ProjectBudgetQueryInterface for integration tests.
 * Storage is keyed by tenantId then projectId to avoid cross-tenant leakage.
 */
final class InMemoryProjectBudgetQuery implements ProjectBudgetQueryInterface
{
    /** @var array<string, array<string, int>> tenantId -> projectId -> minor units */
    private array $laborBudget = [];
    /** @var array<string, array<string, int>> */
    private array $actualLaborCost = [];
    /** @var array<string, array<string, int>> */
    private array $expenseBudget = [];
    /** @var array<string, array<string, int>> */
    private array $actualExpenseCost = [];
    /** @var array<string, array<string, string>> tenantId -> projectId -> currency code */
    private array $currencyByTenantAndProject = [];

    public function setLaborBudget(string $tenantId, string $projectId, Money $amount): void
    {
        $this->laborBudget[$tenantId][$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByTenantAndProject[$tenantId][$projectId] = $amount->getCurrency();
    }

    public function setActualLaborCost(string $tenantId, string $projectId, Money $amount): void
    {
        $this->actualLaborCost[$tenantId][$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByTenantAndProject[$tenantId][$projectId] = $this->currencyByTenantAndProject[$tenantId][$projectId] ?? $amount->getCurrency();
    }

    public function setExpenseBudget(string $tenantId, string $projectId, Money $amount): void
    {
        $this->expenseBudget[$tenantId][$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByTenantAndProject[$tenantId][$projectId] = $this->currencyByTenantAndProject[$tenantId][$projectId] ?? $amount->getCurrency();
    }

    public function setActualExpenseCost(string $tenantId, string $projectId, Money $amount): void
    {
        $this->actualExpenseCost[$tenantId][$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByTenantAndProject[$tenantId][$projectId] = $this->currencyByTenantAndProject[$tenantId][$projectId] ?? $amount->getCurrency();
    }

    private function currencyFor(string $tenantId, string $projectId): string
    {
        return ($this->currencyByTenantAndProject[$tenantId] ?? [])[$projectId] ?? 'MYR';
    }

    public function getLaborBudget(string $tenantId, string $projectId): Money
    {
        $amount = ($this->laborBudget[$tenantId] ?? [])[$projectId] ?? 0;
        return new Money($amount, $this->currencyFor($tenantId, $projectId));
    }

    public function getActualLaborCost(string $tenantId, string $projectId): Money
    {
        $amount = ($this->actualLaborCost[$tenantId] ?? [])[$projectId] ?? 0;
        return new Money($amount, $this->currencyFor($tenantId, $projectId));
    }

    public function getExpenseBudget(string $tenantId, string $projectId): Money
    {
        $amount = ($this->expenseBudget[$tenantId] ?? [])[$projectId] ?? 0;
        return new Money($amount, $this->currencyFor($tenantId, $projectId));
    }

    public function getActualExpenseCost(string $tenantId, string $projectId): Money
    {
        $amount = ($this->actualExpenseCost[$tenantId] ?? [])[$projectId] ?? 0;
        return new Money($amount, $this->currencyFor($tenantId, $projectId));
    }
}
