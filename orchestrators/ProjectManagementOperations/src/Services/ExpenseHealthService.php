<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Services;

use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ExpenseHealthServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\ExpenseHealthDTO;

final readonly class ExpenseHealthService implements ExpenseHealthServiceInterface
{
    public function __construct(
        private ProjectQueryInterface $projectQuery,
        private BudgetQueryInterface $budgetQuery
    ) {
    }

    public function calculate(string $tenantId, string $projectId): ExpenseHealthDTO
    {
        $project = $this->projectQuery->findById($tenantId, $projectId);
        if ($project === null) {
            throw new \InvalidArgumentException("Project with ID {$projectId} not found");
        }

        $budgetedExpenseCost = $this->budgetQuery->getExpenseBudget($tenantId, $projectId);
        $actualExpenseCost = $this->budgetQuery->getActualExpenseCost($tenantId, $projectId);
        
        $healthPercentage = 0.0;
        if ($budgetedExpenseCost->getAmountInMinorUnits() > 0) {
            $healthPercentage = ($actualExpenseCost->getAmountInMinorUnits() / $budgetedExpenseCost->getAmountInMinorUnits()) * 100;
        }

        return new ExpenseHealthDTO(
            projectId: $projectId,
            budgetedExpenseCost: $budgetedExpenseCost,
            actualExpenseCost: $actualExpenseCost,
            healthPercentage: (float) $healthPercentage
        );
    }
}
