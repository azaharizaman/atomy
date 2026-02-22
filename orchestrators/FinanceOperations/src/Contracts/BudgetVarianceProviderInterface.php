<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for budget variance data provider.
 *
 * This interface defines the data retrieval methods needed by the
 * BudgetTrackingCoordinator to perform budget variance analysis.
 *
 * Interface Segregation Compliance:
 * - Defines only data retrieval methods, no business logic
 * - Used by coordinators to aggregate data from multiple sources
 * - Implementation provided by adapters layer
 */
interface BudgetVarianceProviderInterface
{
    /**
     * Get budget data for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @param string|null $budgetVersionId Optional budget version identifier
     * @return array<string, mixed> Budget data
     */
    public function getBudgetData(string $tenantId, string $periodId, ?string $budgetVersionId = null): array;

    /**
     * Get actual data for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Actual data
     */
    public function getActualData(string $tenantId, string $periodId): array;

    /**
     * Get variance analysis for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @param string|null $budgetVersionId Optional budget version identifier
     * @return array<string, mixed> Variance analysis data
     */
    public function getVarianceAnalysis(string $tenantId, string $periodId, ?string $budgetVersionId = null): array;
}
