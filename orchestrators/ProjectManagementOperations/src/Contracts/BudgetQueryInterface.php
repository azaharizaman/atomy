<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

interface BudgetQueryInterface
{
    /**
     * Get labor budget for a project
     */
    public function getLaborBudget(string $projectId): Money;

    /**
     * Get total actual labor cost for a project
     */
    public function getActualLaborCost(string $projectId): Money;
}
