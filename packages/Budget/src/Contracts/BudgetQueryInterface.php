<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

/**
 * Budget Query contract
 * 
 * Provides read-only query access for budget records.
 */
interface BudgetQueryInterface
{
    /**
     * Find budget by cost center and period
     * 
     * @param string $costCenterId Cost center identifier
     * @param string $periodId Period identifier
     * @return BudgetInterface|null
     */
    public function findByCostCenterAndPeriod(string $costCenterId, string $periodId): ?BudgetInterface;
}
