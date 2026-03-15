<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Project-scoped budget data for health calculations.
 * Implemented by the application or a Budget adapter.
 * All methods are tenant-scoped to prevent cross-tenant reads.
 */
interface ProjectBudgetQueryInterface
{
    public function getLaborBudget(string $tenantId, string $projectId): Money;

    public function getActualLaborCost(string $tenantId, string $projectId): Money;

    public function getExpenseBudget(string $tenantId, string $projectId): Money;

    public function getActualExpenseCost(string $tenantId, string $projectId): Money;
}
