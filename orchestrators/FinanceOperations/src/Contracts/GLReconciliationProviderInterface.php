<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for GL reconciliation data provider.
 *
 * This interface defines the data retrieval methods needed by the
 * GLPostingCoordinator to perform GL reconciliation operations.
 *
 * Interface Segregation Compliance:
 * - Defines only data retrieval methods, no business logic
 * - Used by coordinators to aggregate data from multiple sources
 * - Implementation provided by adapters layer
 */
interface GLReconciliationProviderInterface
{
    /**
     * Get subledger balance for reconciliation.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @param string $subledgerType The subledger type (AR, AP, Inventory, etc.)
     * @return array<string, mixed> Subledger balance data
     */
    public function getSubledgerBalance(string $tenantId, string $periodId, string $subledgerType): array;

    /**
     * Get GL balance for reconciliation.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @param string $accountId The GL account identifier
     * @return array<string, mixed> GL balance data
     */
    public function getGLBalance(string $tenantId, string $periodId, string $accountId): array;

    /**
     * Get reconciliation discrepancies for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Discrepancies data
     */
    public function getDiscrepancies(string $tenantId, string $periodId): array;

    /**
     * Get reconciliation status for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Reconciliation status data
     */
    public function getReconciliationStatus(string $tenantId, string $periodId): array;
}
