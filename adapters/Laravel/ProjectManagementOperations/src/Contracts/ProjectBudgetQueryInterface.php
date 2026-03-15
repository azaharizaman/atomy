<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Project-scoped budget data for health calculations.
 * Implemented by the application or a Budget adapter.
 */
interface ProjectBudgetQueryInterface
{
    public function getLaborBudget(string $projectId): Money;

    public function getActualLaborCost(string $projectId): Money;

    public function getExpenseBudget(string $projectId): Money;

    public function getActualExpenseCost(string $projectId): Money;
}
