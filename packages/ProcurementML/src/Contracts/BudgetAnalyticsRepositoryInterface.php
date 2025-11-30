<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

/**
 * Budget analytics repository interface for overrun prediction
 * 
 * Provides budget tracking and forecasting queries for proactive
 * budget management and violation prevention.
 */
interface BudgetAnalyticsRepositoryInterface
{
    /**
     * Get allocated budget for department in period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float Total allocated budget amount
     */
    public function getAllocatedBudget(string $departmentId, string $periodId): float;

    /**
     * Get committed amount (approved but not yet spent)
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float Total committed amount
     */
    public function getCommittedAmount(string $departmentId, string $periodId): float;

    /**
     * Get actual spent amount
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float Total actual expenditure
     */
    public function getActualSpent(string $departmentId, string $periodId): float;

    /**
     * Get total of pending requisitions awaiting approval
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float Total pending requisition amount
     */
    public function getPendingRequisitionTotal(string $departmentId, string $periodId): float;

    /**
     * Get historical burn rate (daily spending rate)
     * 
     * @param string $departmentId Department identifier
     * @return float Average daily spending based on historical data
     */
    public function getHistoricalBurnRate(string $departmentId): float;

    /**
     * Get spending pattern consistency score
     * Higher score = more predictable spending
     * 
     * @param string $departmentId Department identifier
     * @return float Consistency score (0.0 = erratic, 1.0 = very consistent)
     */
    public function getSpendingPattern(string $departmentId): float;

    /**
     * Get historical variance between committed and actual amounts
     * 
     * @param string $departmentId Department identifier
     * @return float Average percentage variance (0.1 = 10% typical variance)
     */
    public function getCommittedVsActualVariance(string $departmentId): float;

    /**
     * Get days remaining in period
     * 
     * @param string $periodId Period identifier
     * @return int Days remaining
     */
    public function getDaysRemainingInPeriod(string $periodId): int;

    /**
     * Get total days in period
     * 
     * @param string $periodId Period identifier
     * @return int Total days in period
     */
    public function getTotalDaysInPeriod(string $periodId): int;

    /**
     * Get department seasonality factor for current month
     * 
     * @param string $departmentId Department identifier
     * @return float Seasonal multiplier (1.2 = 20% higher spending than average)
     */
    public function getDepartmentSeasonalityFactor(string $departmentId): float;

    /**
     * Get emergency purchase frequency
     * 
     * @param string $departmentId Department identifier
     * @return float Ratio of emergency to total purchases (0.0 to 1.0)
     */
    public function getEmergencyPurchaseFrequency(string $departmentId): float;

    /**
     * Get budget amendment count for period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return int Number of budget amendments
     */
    public function getBudgetAmendmentCount(string $departmentId, string $periodId): int;

    /**
     * Get total budget amendment amount for period
     * 
     * @param string $departmentId Department identifier
     * @param string $periodId Period identifier
     * @return float Total amendment amount (can be negative for reductions)
     */
    public function getBudgetAmendmentTotal(string $departmentId, string $periodId): float;

    /**
     * Get shared budget pool available to department
     * 
     * @param string $departmentId Department identifier
     * @return float Amount available from shared budget pool
     */
    public function getSharedBudgetPool(string $departmentId): float;

    /**
     * Get project budget utilization
     * 
     * @param string $projectId Project identifier
     * @return float Utilization percentage (0.0 to 1.0+)
     */
    public function getProjectBudgetUtilization(string $projectId): float;

    /**
     * Get currency exposure risk
     * 
     * @param string $departmentId Department identifier
     * @param string $currency Currency code
     * @return float Exposure risk score (0.0 = no risk, 1.0 = high risk)
     */
    public function getCurrencyExposureRisk(string $departmentId, string $currency): float;
}
