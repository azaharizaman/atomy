<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetQueryInterface;

/**
 * In-memory app ProjectBudgetQueryInterface for integration tests.
 */
final class InMemoryProjectBudgetQuery implements ProjectBudgetQueryInterface
{
    private string $currency = 'MYR';

    /** @var array<string, int> projectId -> minor units */
    private array $laborBudget = [];
    /** @var array<string, int> */
    private array $actualLaborCost = [];
    /** @var array<string, int> */
    private array $expenseBudget = [];
    /** @var array<string, int> */
    private array $actualExpenseCost = [];

    public function setLaborBudget(string $projectId, Money $amount): void
    {
        $this->laborBudget[$projectId] = $amount->getAmountInMinorUnits();
        $this->currency = $amount->getCurrency();
    }

    public function setActualLaborCost(string $projectId, Money $amount): void
    {
        $this->actualLaborCost[$projectId] = $amount->getAmountInMinorUnits();
    }

    public function setExpenseBudget(string $projectId, Money $amount): void
    {
        $this->expenseBudget[$projectId] = $amount->getAmountInMinorUnits();
    }

    public function setActualExpenseCost(string $projectId, Money $amount): void
    {
        $this->actualExpenseCost[$projectId] = $amount->getAmountInMinorUnits();
    }

    public function getLaborBudget(string $projectId): Money
    {
        return new Money($this->laborBudget[$projectId] ?? 0, $this->currency);
    }

    public function getActualLaborCost(string $projectId): Money
    {
        return new Money($this->actualLaborCost[$projectId] ?? 0, $this->currency);
    }

    public function getExpenseBudget(string $projectId): Money
    {
        return new Money($this->expenseBudget[$projectId] ?? 0, $this->currency);
    }

    public function getActualExpenseCost(string $projectId): Money
    {
        return new Money($this->actualExpenseCost[$projectId] ?? 0, $this->currency);
    }
}
