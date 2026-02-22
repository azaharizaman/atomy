<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Budget Query Interface for FinanceOperations.
 *
 * Defines the query methods needed by FinanceOperations DataProviders
 * to fetch budget data from the Budget package.
 */
interface BudgetQueryInterface
{
    /**
     * Get budgets by period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @param string|null $budgetVersionId Optional budget version identifier
     * @return iterable Budget entities
     */
    public function getBudgetsByPeriod(string $tenantId, string $periodId, ?string $budgetVersionId = null): iterable;
}
