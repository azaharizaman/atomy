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

    /**
     * Get transactions for a specific account.
     *
     * @param string $tenantId Tenant identifier
     * @param string $accountId Account identifier or code
     * @return iterable<int, object> Transaction projections
     */
    public function getAccountTransactions(string $tenantId, string $accountId): iterable;

    /**
     * Get account balance snapshot for a period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $accountId Account identifier or code
     * @param string $periodId Period identifier
     * @return object Balance projection
     */
    public function getAccountBalance(string $tenantId, string $accountId, string $periodId): object;

    /**
     * Get cost center balance from ledger.
     *
     * @param string $tenantId Tenant identifier
     * @param string $costCenterId Cost center identifier
     * @return object Balance projection
     */
    public function getCostCenterBalance(string $tenantId, string $costCenterId): object;
}
