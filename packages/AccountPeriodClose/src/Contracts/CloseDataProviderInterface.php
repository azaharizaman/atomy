<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Contracts;

/**
 * Contract for providing period close data.
 *
 * This interface is implemented by the orchestrator to fetch
 * data from Nexus\Finance, Nexus\Period, and related packages.
 */
interface CloseDataProviderInterface
{
    /**
     * Get trial balance for a period.
     *
     * @param string $periodId
     * @return array<string, float>
     */
    public function getTrialBalance(string $periodId): array;

    /**
     * Get income statement accounts and balances.
     *
     * @param string $periodId
     * @return array<string, float>
     */
    public function getIncomeStatementBalances(string $periodId): array;

    /**
     * Get unposted entries for a period.
     *
     * @param string $periodId
     * @return array<array<string, mixed>>
     */
    public function getUnpostedEntries(string $periodId): array;

    /**
     * Check if all reconciliations are complete.
     *
     * @param string $periodId
     * @return bool
     */
    public function areReconciliationsComplete(string $periodId): bool;
}
