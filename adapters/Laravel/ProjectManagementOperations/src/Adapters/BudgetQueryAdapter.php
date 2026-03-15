<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetQueryInterface;

/**
 * Implements orchestrator BudgetQueryInterface using project-scoped budget data.
 */
final readonly class BudgetQueryAdapter implements BudgetQueryInterface
{
    public function __construct(
        private ProjectBudgetQueryInterface $projectBudgetQuery,
    ) {
    }

    public function getLaborBudget(string $projectId): Money
    {
        return $this->projectBudgetQuery->getLaborBudget($projectId);
    }

    public function getActualLaborCost(string $projectId): Money
    {
        return $this->projectBudgetQuery->getActualLaborCost($projectId);
    }

    public function getExpenseBudget(string $projectId): Money
    {
        return $this->projectBudgetQuery->getExpenseBudget($projectId);
    }

    public function getActualExpenseCost(string $projectId): Money
    {
        return $this->projectBudgetQuery->getActualExpenseCost($projectId);
    }
}
