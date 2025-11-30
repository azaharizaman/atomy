<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Budget\ValueObjects\BudgetConsolidation;
use Nexus\Budget\ValueObjects\ManagerPerformanceScore;

/**
 * Budget Analytics Repository contract
 * 
 * Provides read-optimized aggregated queries for budget analytics and dashboards.
 */
interface BudgetAnalyticsRepositoryInterface
{
    /**
     * Get allocated budget for department and period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float
     */
    public function getAllocatedBudget(string $departmentId, string $periodId): float;

    /**
     * Get committed amount for department and period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float
     */
    public function getCommittedAmount(string $departmentId, string $periodId): float;

    /**
     * Get actual spent for department and period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float
     */
    public function getActualSpent(string $departmentId, string $periodId): float;

    /**
     * Get average burn rate for department (historical)
     * 
     * @param string $departmentId Department identifier
     * @return float Daily burn rate
     */
    public function getAverageBurnRate(string $departmentId): float;

    /**
     * Get seasonality factor for department and period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float Seasonality multiplier
     */
    public function getSeasonalityFactor(string $departmentId, string $periodId): float;

    /**
     * Get consolidated budget for department hierarchy
     * 
     * @param string $parentDepartmentId Parent department identifier
     * @param string $periodId Period identifier
     * @return BudgetConsolidation
     */
    public function getConsolidatedBudget(string $parentDepartmentId, string $periodId): BudgetConsolidation;

    /**
     * Get burn rate breakdown by department
     * 
     * @param string $periodId Period identifier
     * @return array<string, float> Department ID => burn rate
     */
    public function getBurnRateByDepartment(string $periodId): array;

    /**
     * Get variance trends for period range
     * 
     * @param string $budgetId Budget identifier
     * @param int $periodCount Number of periods to analyze
     * @return array<array{period_id: string, variance: float, variance_pct: float}>
     */
    public function getVarianceTrends(string $budgetId, int $periodCount): array;

    /**
     * Get department rankings by variance
     * 
     * @param string $periodId Period identifier
     * @param int $limit Number of results
     * @return array<array{department_id: string, variance_pct: float, rank: int}>
     */
    public function getDepartmentRankings(string $periodId, int $limit = 10): array;

    /**
     * Get manager performance score
     * 
     * @param string $managerId Manager identifier
     * @param string $periodId Period identifier
     * @return ManagerPerformanceScore
     */
    public function getManagerPerformanceScore(string $managerId, string $periodId): ManagerPerformanceScore;

    /**
     * Get utilization breakdown
     * 
     * @param string $periodId Period identifier
     * @return array<array{account_id: string, department_id: string, utilization_pct: float}>
     */
    public function getUtilizationBreakdown(string $periodId): array;
}
