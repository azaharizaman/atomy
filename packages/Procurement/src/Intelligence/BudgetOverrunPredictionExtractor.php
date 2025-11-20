<?php

declare(strict_types=1);

namespace Nexus\Procurement\Intelligence;

use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;
use Nexus\Procurement\Contracts\BudgetAnalyticsRepositoryInterface;

/**
 * Feature extractor for budget overrun prediction
 * 
 * Extracts 16 features to predict if a requisition will cause budget
 * overrun before approval, enabling proactive budget reallocation.
 * 
 * Usage: Real-time budget impact assessment during requisition
 * approval workflow to prevent violations.
 */
final readonly class BudgetOverrunPredictionExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private BudgetAnalyticsRepositoryInterface $budgetAnalytics
    ) {}

    /**
     * Extract budget overrun features from requisition
     * 
     * @param object $requisition Expected to have departmentId, totalAmount, periodId, projectId, etc.
     * @return FeatureSetInterface
     */
    public function extract(object $requisition): FeatureSetInterface
    {
        $departmentId = $requisition->departmentId ?? '';
        $totalAmount = (float)($requisition->totalAmount ?? 0.0);
        $periodId = $requisition->periodId ?? '';
        $projectId = $requisition->projectId ?? null;
        $isEmergency = (bool)($requisition->isEmergency ?? false);
        $currency = $requisition->currency ?? 'MYR';
        
        // Current budget status
        $allocatedBudget = $this->budgetAnalytics->getAllocatedBudget($departmentId, $periodId);
        $committedAmount = $this->budgetAnalytics->getCommittedAmount($departmentId, $periodId);
        $actualSpent = $this->budgetAnalytics->getActualSpent($departmentId, $periodId);
        $pendingRequisitions = $this->budgetAnalytics->getPendingRequisitionTotal($departmentId, $periodId);
        
        // Historical spending patterns
        $historicalBurnRate = $this->budgetAnalytics->getHistoricalBurnRate($departmentId);
        $spendingPattern = $this->budgetAnalytics->getSpendingPattern($departmentId);
        $varianceHistory = $this->budgetAnalytics->getCommittedVsActualVariance($departmentId);
        
        // Period analysis
        $daysRemainingInPeriod = $this->budgetAnalytics->getDaysRemainingInPeriod($periodId);
        $totalDaysInPeriod = $this->budgetAnalytics->getTotalDaysInPeriod($periodId);
        
        // Seasonality and trends
        $departmentSeasonality = $this->budgetAnalytics->getDepartmentSeasonalityFactor($departmentId);
        $emergencyPurchaseFreq = $this->budgetAnalytics->getEmergencyPurchaseFrequency($departmentId);
        
        // Budget amendment history
        $amendmentCount = $this->budgetAnalytics->getBudgetAmendmentCount($departmentId, $periodId);
        $amendmentTotalAmount = $this->budgetAnalytics->getBudgetAmendmentTotal($departmentId, $periodId);
        
        // Cross-department budget sharing
        $sharedBudgetAvailable = $this->budgetAnalytics->getSharedBudgetPool($departmentId);
        
        // Project-specific if applicable
        $projectBudgetUtilization = $projectId 
            ? $this->budgetAnalytics->getProjectBudgetUtilization($projectId)
            : 0.0;
        
        // Currency exposure
        $currencyExposure = $this->budgetAnalytics->getCurrencyExposureRisk($departmentId, $currency);
        
        // Calculate engineered features
        $currentUtilization = $allocatedBudget > 0 ? ($actualSpent / $allocatedBudget) : 0.0;
        $projectedUtilization = $allocatedBudget > 0 
            ? (($actualSpent + $committedAmount + $pendingRequisitions + $totalAmount) / $allocatedBudget)
            : 0.0;
        $availableBudget = $allocatedBudget - $actualSpent - $committedAmount - $pendingRequisitions;
        $overrunAmount = max(0.0, $totalAmount - $availableBudget);
        $periodProgress = $totalDaysInPeriod > 0 
            ? (($totalDaysInPeriod - $daysRemainingInPeriod) / $totalDaysInPeriod)
            : 0.0;
        $burnRateRatio = $periodProgress > 0 ? ($currentUtilization / $periodProgress) : 1.0;
        $projectedOverrun = max(0.0, $projectedUtilization - 1.0);
        
        $features = [
            // === Current Budget Status (4 features) ===
            'allocated_budget' => $allocatedBudget,
            'committed_amount' => $committedAmount,
            'actual_spent' => $actualSpent,
            'pending_requisitions_total' => $pendingRequisitions,
            
            // === Historical Patterns (3 features) ===
            'historical_burn_rate' => $historicalBurnRate, // Daily spending rate
            'spending_pattern_score' => $spendingPattern, // 0-1 consistency score
            'committed_vs_actual_variance' => $varianceHistory, // Historical variance percentage
            
            // === Period Analysis (3 features) ===
            'days_remaining_in_period' => $daysRemainingInPeriod,
            'period_progress_pct' => $periodProgress, // 0.0 to 1.0
            'total_days_in_period' => $totalDaysInPeriod,
            
            // === Seasonality & Trends (2 features) ===
            'department_seasonality_factor' => $departmentSeasonality, // Multiplier
            'emergency_purchase_frequency' => $emergencyPurchaseFreq, // Ratio
            
            // === Budget Amendments (2 features) ===
            'amendment_count' => $amendmentCount,
            'amendment_total_amount' => $amendmentTotalAmount,
            
            // === Sharing & Project (2 features) ===
            'shared_budget_available' => $sharedBudgetAvailable,
            'project_budget_utilization' => $projectBudgetUtilization,
            
            // === Currency Risk (1 feature) ===
            'currency_exposure_risk' => $currencyExposure,
            
            // === Engineered Features (7 features) ===
            'current_utilization_pct' => $currentUtilization,
            'projected_utilization_pct' => $projectedUtilization,
            'available_budget' => $availableBudget,
            'immediate_overrun_amount' => $overrunAmount,
            'burn_rate_ratio' => $burnRateRatio, // Actual vs expected burn rate
            'projected_overrun_pct' => $projectedOverrun,
            'is_emergency_purchase' => $isEmergency ? 1 : 0,
        ];

        $metadata = [
            'entity_type' => 'requisition_budget_check',
            'department_id' => $departmentId,
            'period_id' => $periodId,
            'project_id' => $projectId,
            'requisition_amount' => $totalAmount,
            'extracted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }

    public function getFeatureKeys(): array
    {
        return [
            // Current status
            'allocated_budget',
            'committed_amount',
            'actual_spent',
            'pending_requisitions_total',
            
            // Historical
            'historical_burn_rate',
            'spending_pattern_score',
            'committed_vs_actual_variance',
            
            // Period
            'days_remaining_in_period',
            'period_progress_pct',
            'total_days_in_period',
            
            // Seasonality
            'department_seasonality_factor',
            'emergency_purchase_frequency',
            
            // Amendments
            'amendment_count',
            'amendment_total_amount',
            
            // Sharing
            'shared_budget_available',
            'project_budget_utilization',
            
            // Currency
            'currency_exposure_risk',
            
            // Engineered
            'current_utilization_pct',
            'projected_utilization_pct',
            'available_budget',
            'immediate_overrun_amount',
            'burn_rate_ratio',
            'projected_overrun_pct',
            'is_emergency_purchase',
        ];
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }
}
