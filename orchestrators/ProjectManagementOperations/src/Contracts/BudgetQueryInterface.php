<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

interface BudgetQueryInterface
{
    /**
     * Get labor budget for a project (tenant-scoped).
     */
    public function getLaborBudget(string $tenantId, string $projectId): Money;

    /**
     * Get total actual labor cost for a project (tenant-scoped).
     */
    public function getActualLaborCost(string $tenantId, string $projectId): Money;

    /**
     * Get expense budget for a project (tenant-scoped).
     */
    public function getExpenseBudget(string $tenantId, string $projectId): Money;

    /**
     * Get total actual expense cost for a project (tenant-scoped).
     */
    public function getActualExpenseCost(string $tenantId, string $projectId): Money;
}
