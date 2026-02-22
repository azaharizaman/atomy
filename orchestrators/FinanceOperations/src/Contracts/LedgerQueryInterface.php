<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Ledger Query Interface for FinanceOperations.
 *
 * Defines the query methods needed by FinanceOperations DataProviders
 * to fetch ledger/actual data from the JournalEntry package.
 */
interface LedgerQueryInterface
{
    /**
     * Get actual amounts by period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return iterable Actual amount entities
     */
    public function getActualsByPeriod(string $tenantId, string $periodId): iterable;
}
