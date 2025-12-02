<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Contracts;

/**
 * Contract for providing variance data.
 *
 * This interface is implemented by the orchestrator to fetch
 * data from Nexus\Finance, Nexus\Budget, and related packages.
 */
interface VarianceDataProviderInterface
{
    /**
     * Get actual balances for a period.
     *
     * @param string $entityId
     * @param \DateTimeImmutable $periodStart
     * @param \DateTimeImmutable $periodEnd
     * @return array<string, float> Account code to balance
     */
    public function getActualBalances(
        string $entityId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array;

    /**
     * Get budget balances for a period.
     *
     * @param string $entityId
     * @param \DateTimeImmutable $periodStart
     * @param \DateTimeImmutable $periodEnd
     * @return array<string, float> Account code to budget
     */
    public function getBudgetBalances(
        string $entityId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array;

    /**
     * Get prior period balances for comparison.
     *
     * @param string $entityId
     * @param \DateTimeImmutable $periodStart
     * @param \DateTimeImmutable $periodEnd
     * @return array<string, float>
     */
    public function getPriorPeriodBalances(
        string $entityId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array;
}
