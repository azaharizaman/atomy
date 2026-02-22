<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Cost Query Interface for FinanceOperations.
 *
 * Defines the query methods needed by FinanceOperations DataProviders
 * to fetch cost accounting data.
 */
interface CostQueryInterface
{
    /**
     * Get committed costs for a period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return iterable Cost entities with committed amounts
     */
    public function getCommittedCosts(string $tenantId, string $periodId): iterable;
}
