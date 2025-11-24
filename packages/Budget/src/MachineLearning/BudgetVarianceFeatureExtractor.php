<?php

declare(strict_types=1);

namespace Nexus\Budget\MachineLearning;

use Nexus\Budget\Contracts\BudgetAnalyticsRepositoryInterface;
use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\Contracts\FeatureSetInterface;
use Nexus\MachineLearning\ValueObjects\FeatureSet;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Enums\PeriodType;
use Nexus\Setting\Contracts\SettingsManagerInterface;

/**
 * Budget Variance Feature Extractor
 * 
 * Extracts features from budget entities for AI-powered overrun prediction.
 * Implements the Intelligence package's FeatureExtractorInterface.
 */
final readonly class BudgetVarianceFeatureExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private BudgetAnalyticsRepositoryInterface $analyticsRepository,
        private PeriodManagerInterface $periodManager,
        private SettingsManagerInterface $settings
    ) {}

    /**
     * Extract features from budget entity
     * 
     * @param object $budget BudgetInterface instance
     * @return FeatureSetInterface
     * @throws \InvalidArgumentException If input is not a BudgetInterface
     */
    public function extract(object $budget): FeatureSetInterface
    {
        if (!$budget instanceof BudgetInterface) {
            throw new \InvalidArgumentException(
                'BudgetVarianceFeatureExtractor requires BudgetInterface, got ' . get_class($budget)
            );
        }

        $features = [
            // Current status
            'allocated_budget' => (float) $budget->getAllocatedAmount()->getAmount(),
            'committed_amount' => (float) $budget->getCommittedAmount()->getAmount(),
            'actual_spent' => (float) $budget->getActualAmount()->getAmount(),
            'available_amount' => (float) $budget->getAvailableAmount()->getAmount(),
            'current_utilization_pct' => $this->calculateUtilization($budget),
            
            // Period progress
            'period_days_elapsed_ratio' => $this->calculatePeriodElapsedRatio($budget->getPeriodId()),
            'days_remaining_in_period' => $this->getDaysRemaining($budget->getPeriodId()),
            
            // Consumption metrics
            'consumption_to_date_ratio' => $this->calculateConsumptionRatio($budget),
            'encumbrance_ratio' => $this->calculateEncumbranceRatio($budget),
            
            // Historical patterns
            'historical_burn_rate' => $this->getHistoricalBurnRate($budget),
            'seasonal_factor' => $this->getSeasonalityFactor($budget),
            
            // Projected metrics
            'projected_utilization_pct' => $this->calculateProjectedUtilization($budget),
            
            // Risk indicators
            'department_head_risk_score' => $this->getDepartmentHeadRiskScore($budget),
            'threshold_exceeded' => $this->isAlertThresholdExceeded($budget),
            
            // Contextual features
            'hierarchy_level' => $budget->getHierarchyLevel(),
            'parent_budget_utilization_pct' => $this->getParentUtilization($budget),
            'is_revenue_budget' => $budget->isRevenueBudget() ? 1.0 : 0.0,
        ];

        $metadata = [
            'budget_id' => $budget->getId(),
            'period_id' => $budget->getPeriodId(),
            'department_id' => $budget->getDepartmentId(),
            'extracted_at' => date('Y-m-d H:i:s'),
        ];

        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }

    /**
     * Calculate current utilization percentage
     */
    private function calculateUtilization(BudgetInterface $budget): float
    {
        $allocated = $budget->getAllocatedAmount()->getAmount();
        if ($allocated == 0) {
            return 0.0;
        }

        $actual = $budget->getActualAmount()->getAmount();
        return ($actual / $allocated) * 100;
    }

    /**
     * Calculate period elapsed ratio (0.0 to 1.0)
     */
    private function calculatePeriodElapsedRatio(string $periodId): float
    {
        try {
            $period = $this->periodManager->findById($periodId);
            if (!$period) {
                return 0.5; // Default to mid-period
            }

            $start = $period->getStartDate();
            $end = $period->getEndDate();
            $now = new \DateTimeImmutable();

            if ($now < $start) {
                return 0.0;
            }
            if ($now > $end) {
                return 1.0;
            }

            $totalDays = $start->diff($end)->days;
            $elapsedDays = $start->diff($now)->days;

            return $totalDays > 0 ? ($elapsedDays / $totalDays) : 0.5;
        } catch (\Exception $e) {
            return 0.5;
        }
    }

    /**
     * Calculate days remaining in period
     */
    private function getDaysRemaining(string $periodId): float
    {
        try {
            $period = $this->periodManager->findById($periodId);
            if (!$period) {
                return 15.0; // Default estimate
            }

            $end = $period->getEndDate();
            $now = new \DateTimeImmutable();

            if ($now > $end) {
                return 0.0;
            }

            return (float) $now->diff($end)->days;
        } catch (\Exception $e) {
            return 15.0;
        }
    }

    /**
     * Calculate consumption to date ratio
     */
    private function calculateConsumptionRatio(BudgetInterface $budget): float
    {
        $allocated = $budget->getAllocatedAmount()->getAmount();
        if ($allocated == 0) {
            return 0.0;
        }

        $actual = $budget->getActualAmount()->getAmount();
        return $actual / $allocated;
    }

    /**
     * Calculate encumbrance ratio
     */
    private function calculateEncumbranceRatio(BudgetInterface $budget): float
    {
        $allocated = $budget->getAllocatedAmount()->getAmount();
        if ($allocated == 0) {
            return 0.0;
        }

        $committed = $budget->getCommittedAmount()->getAmount();
        return $committed / $allocated;
    }

    /**
     * Get historical burn rate for department
     */
    private function getHistoricalBurnRate(BudgetInterface $budget): float
    {
        $departmentId = $budget->getDepartmentId();
        if (!$departmentId) {
            return 0.0;
        }

        try {
            return $this->analyticsRepository->getAverageBurnRate($departmentId);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get seasonality factor
     */
    private function getSeasonalityFactor(BudgetInterface $budget): float
    {
        $departmentId = $budget->getDepartmentId();
        $periodId = $budget->getPeriodId();
        
        if (!$departmentId) {
            return 1.0; // Neutral factor
        }

        try {
            return $this->analyticsRepository->getSeasonalityFactor($departmentId, $periodId);
        } catch (\Exception $e) {
            return 1.0;
        }
    }

    /**
     * Calculate projected utilization at period end
     */
    private function calculateProjectedUtilization(BudgetInterface $budget): float
    {
        $currentUtilization = $this->calculateUtilization($budget);
        $periodElapsed = $this->calculatePeriodElapsedRatio($budget->getPeriodId());

        if ($periodElapsed == 0) {
            return 0.0;
        }

        // Linear projection based on current burn rate
        $burnRate = $currentUtilization / $periodElapsed;
        return min(200.0, $burnRate * 1.0); // Cap at 200%
    }

    /**
     * Get department head risk score from performance history
     */
    private function getDepartmentHeadRiskScore(BudgetInterface $budget): float
    {
        // This would integrate with HRM package to get manager ID
        // For now, return a neutral score
        return 50.0; // 0-100 scale, 50 = average risk
    }

    /**
     * Check if alert threshold is exceeded
     */
    private function isAlertThresholdExceeded(BudgetInterface $budget): float
    {
        $utilizationPct = $this->calculateUtilization($budget);
        $threshold = $this->settings->getFloat('budget.alert_threshold_percentage', 85.0);

        return $utilizationPct >= $threshold ? 1.0 : 0.0;
    }

    /**
     * Get parent budget utilization if hierarchical
     */
    private function getParentUtilization(BudgetInterface $budget): float
    {
        $parentId = $budget->getParentBudgetId();
        if (!$parentId) {
            return 0.0; // No parent
        }

        // This would query parent budget and calculate its utilization
        // For now, return 0 (would need BudgetRepository injection)
        return 0.0;
    }
}
