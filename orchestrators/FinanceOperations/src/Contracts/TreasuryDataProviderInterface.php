<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for treasury data provider.
 *
 * This interface defines the data retrieval methods needed by the
 * CashFlowCoordinator to perform treasury operations.
 *
 * Interface Segregation Compliance:
 * - Defines only data retrieval methods, no business logic
 * - Used by coordinators to aggregate data from multiple sources
 * - Implementation provided by adapters layer
 */
interface TreasuryDataProviderInterface
{
    /**
     * Get cash position for a bank account.
     *
     * @param string $tenantId The tenant identifier
     * @param string $bankAccountId The bank account identifier
     * @return array<string, mixed> Cash position data
     */
    public function getCashPosition(string $tenantId, string $bankAccountId): array;

    /**
     * Get cash flow forecast data for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Cash flow forecast data
     */
    public function getCashFlowForecast(string $tenantId, string $periodId): array;

    /**
     * Get bank reconciliation data for a bank account.
     *
     * @param string $tenantId The tenant identifier
     * @param string $bankAccountId The bank account identifier
     * @return array<string, mixed> Bank reconciliation data
     */
    public function getBankReconciliationData(string $tenantId, string $bankAccountId): array;

    /**
     * Get all bank accounts for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return array<int, array<string, mixed>> List of bank accounts
     */
    public function getBankAccounts(string $tenantId): array;
}
