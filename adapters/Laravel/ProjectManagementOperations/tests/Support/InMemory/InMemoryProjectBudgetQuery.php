<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetQueryInterface;

/**
 * In-memory app ProjectBudgetQueryInterface for integration tests.
 * Currency is stored per project to support different currencies per project.
 */
final class InMemoryProjectBudgetQuery implements ProjectBudgetQueryInterface
{
    /** @var array<string, int> projectId -> minor units */
    private array $laborBudget = [];
    /** @var array<string, int> */
    private array $actualLaborCost = [];
    /** @var array<string, int> */
    private array $expenseBudget = [];
    /** @var array<string, int> */
    private array $actualExpenseCost = [];
    /** @var array<string, string> projectId -> currency code */
    private array $currencyByProject = [];

    public function setLaborBudget(string $projectId, Money $amount): void
    {
        $this->laborBudget[$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByProject[$projectId] = $amount->getCurrency();
    }

    public function setActualLaborCost(string $projectId, Money $amount): void
    {
        $this->actualLaborCost[$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByProject[$projectId] = $this->currencyByProject[$projectId] ?? $amount->getCurrency();
    }

    public function setExpenseBudget(string $projectId, Money $amount): void
    {
        $this->expenseBudget[$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByProject[$projectId] = $this->currencyByProject[$projectId] ?? $amount->getCurrency();
    }

    public function setActualExpenseCost(string $projectId, Money $amount): void
    {
        $this->actualExpenseCost[$projectId] = $amount->getAmountInMinorUnits();
        $this->currencyByProject[$projectId] = $this->currencyByProject[$projectId] ?? $amount->getCurrency();
    }

    private function currencyFor(string $projectId): string
    {
        return $this->currencyByProject[$projectId] ?? 'MYR';
    }

    public function getLaborBudget(string $tenantId, string $projectId): Money
    {
        return new Money($this->laborBudget[$projectId] ?? 0, $this->currencyFor($projectId));
    }

    public function getActualLaborCost(string $tenantId, string $projectId): Money
    {
        return new Money($this->actualLaborCost[$projectId] ?? 0, $this->currencyFor($projectId));
    }

    public function getExpenseBudget(string $tenantId, string $projectId): Money
    {
        return new Money($this->expenseBudget[$projectId] ?? 0, $this->currencyFor($projectId));
    }

    public function getActualExpenseCost(string $tenantId, string $projectId): Money
    {
        return new Money($this->actualExpenseCost[$projectId] ?? 0, $this->currencyFor($projectId));
    }
}
