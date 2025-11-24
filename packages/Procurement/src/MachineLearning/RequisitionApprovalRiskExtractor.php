<?php

declare(strict_types=1);

namespace Nexus\Procurement\MachineLearning;

use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\Contracts\FeatureSetInterface;
use Nexus\MachineLearning\ValueObjects\FeatureSet;
use Nexus\Procurement\Contracts\ApprovalAnalyticsRepositoryInterface;

/**
 * Feature extractor for requisition approval risk prediction
 * 
 * Extracts 20 features to predict approval delays and identify
 * high-risk requisitions requiring expedited review or intervention.
 * 
 * Usage: Real-time risk assessment on requisition submission to
 * prioritize critical requisitions and improve cycle time.
 */
final readonly class RequisitionApprovalRiskExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';

    public function __construct(
        private ApprovalAnalyticsRepositoryInterface $approvalAnalytics
    ) {}

    /**
     * Extract approval risk features from requisition
     * 
     * @param object $requisition Expected to have requesterId, departmentId, totalAmount, deliveryDate, etc.
     * @return FeatureSetInterface
     */
    public function extract(object $requisition): FeatureSetInterface
    {
        $requesterId = $requisition->requesterId ?? '';
        $departmentId = $requisition->departmentId ?? '';
        $totalAmount = (float)($requisition->totalAmount ?? 0.0);
        $lineCount = (int)($requisition->lineCount ?? 0);
        $categoryId = $requisition->primaryCategoryId ?? '';
        $deliveryDate = $requisition->deliveryDate ?? null;
        $crossDepartment = (bool)($requisition->crossDepartment ?? false);
        $amendmentCount = (int)($requisition->amendmentCount ?? 0);
        
        // Requester historical performance
        $requesterApprovalRate = $this->approvalAnalytics->getRequesterApprovalRate($requesterId);
        $requesterAvgDuration = $this->approvalAnalytics->getRequesterAvgApprovalDuration($requesterId);
        $requesterRejectionReasons = $this->approvalAnalytics->getRequesterTopRejectionReasons($requesterId);
        
        // Budget analysis
        $budgetUtilization = $this->approvalAnalytics->getDepartmentBudgetUtilization($departmentId);
        $spendingVelocity = $this->approvalAnalytics->getDepartmentSpendingVelocity($departmentId);
        $budgetRemaining = $this->approvalAnalytics->getDepartmentBudgetRemaining($departmentId);
        
        // Approval chain complexity
        $approvalLevels = $this->approvalAnalytics->getRequiredApprovalLevels($totalAmount, $departmentId);
        $approverWorkload = $this->approvalAnalytics->getPrimaryApproverWorkload($departmentId);
        
        // Historical category performance
        $categoryAvgDuration = $this->approvalAnalytics->getCategoryAvgApprovalDuration($categoryId);
        $categoryRejectionRate = $this->approvalAnalytics->getCategoryRejectionRate($categoryId);
        
        // Urgency calculation
        $daysUntilDelivery = $deliveryDate ? $this->calculateDaysUntil($deliveryDate) : 999;
        $isWeekendSubmission = $this->isWeekendOrHoliday();
        
        // Requester-approver relationship
        $relationshipHistory = $this->approvalAnalytics->getRequesterApproverRelationshipScore($requesterId, $departmentId);
        
        // Compliance and complexity
        $complianceFlags = $this->approvalAnalytics->getComplianceRequirementCount($categoryId);
        $technicalComplexity = $this->approvalAnalytics->getTechnicalComplexityScore($requisition);
        
        // Value concentration risk
        $valueConcentration = $lineCount > 0 ? $this->calculateValueConcentration($requisition) : 0.0;
        
        // Calculate engineered features
        $budgetOverrunRisk = ($totalAmount > $budgetRemaining) ? 1 : 0;
        $urgencyScore = $this->calculateUrgencyScore($daysUntilDelivery, $categoryAvgDuration);
        $workloadPenalty = min($approverWorkload / 20.0, 1.0); // Normalize to 0-1 (20+ pending = max penalty)
        
        $features = [
            // === Requester Historical Performance (3 features) ===
            'requester_approval_rate' => $requesterApprovalRate, // 0.0 to 1.0
            'requester_avg_approval_days' => $requesterAvgDuration,
            'requester_rejection_count' => count($requesterRejectionReasons),
            
            // === Budget Analysis (4 features) ===
            'dept_budget_utilization_pct' => $budgetUtilization, // 0.0 to 1.0+
            'dept_spending_velocity' => $spendingVelocity, // Amount per day
            'dept_budget_remaining' => $budgetRemaining,
            'budget_overrun_risk' => $budgetOverrunRisk, // Boolean flag
            
            // === Approval Chain Complexity (3 features) ===
            'required_approval_levels' => $approvalLevels,
            'primary_approver_workload' => $approverWorkload, // Number of pending approvals
            'cross_department_flag' => $crossDepartment ? 1 : 0,
            
            // === Historical Category Performance (2 features) ===
            'category_avg_approval_days' => $categoryAvgDuration,
            'category_rejection_rate' => $categoryRejectionRate,
            
            // === Urgency Indicators (3 features) ===
            'days_until_delivery' => $daysUntilDelivery,
            'weekend_holiday_submission' => $isWeekendSubmission ? 1 : 0,
            'urgency_score' => $urgencyScore, // 0.0 (not urgent) to 1.0 (critical)
            
            // === Requester-Approver Relationship (1 feature) ===
            'relationship_score' => $relationshipHistory, // 0.0 (no history) to 1.0 (strong positive)
            
            // === Compliance & Complexity (3 features) ===
            'compliance_requirement_count' => $complianceFlags,
            'technical_complexity_score' => $technicalComplexity, // 0.0 to 1.0
            'amendment_count' => $amendmentCount,
            
            // === Value Analysis (2 features) ===
            'total_amount' => $totalAmount,
            'value_concentration' => $valueConcentration, // Gini coefficient (0=distributed, 1=concentrated)
            
            // === Engineered Features (2 features) ===
            'workload_penalty' => $workloadPenalty, // 0.0 to 1.0
            'line_count' => $lineCount,
        ];

        $metadata = [
            'entity_type' => 'purchase_requisition',
            'requester_id' => $requesterId,
            'department_id' => $departmentId,
            'total_amount' => $totalAmount,
            'delivery_date' => $deliveryDate,
            'extracted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }

    public function getFeatureKeys(): array
    {
        return [
            // Requester performance
            'requester_approval_rate',
            'requester_avg_approval_days',
            'requester_rejection_count',
            
            // Budget
            'dept_budget_utilization_pct',
            'dept_spending_velocity',
            'dept_budget_remaining',
            'budget_overrun_risk',
            
            // Approval chain
            'required_approval_levels',
            'primary_approver_workload',
            'cross_department_flag',
            
            // Category performance
            'category_avg_approval_days',
            'category_rejection_rate',
            
            // Urgency
            'days_until_delivery',
            'weekend_holiday_submission',
            'urgency_score',
            
            // Relationship
            'relationship_score',
            
            // Compliance
            'compliance_requirement_count',
            'technical_complexity_score',
            'amendment_count',
            
            // Value
            'total_amount',
            'value_concentration',
            
            // Engineered
            'workload_penalty',
            'line_count',
        ];
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }

    /**
     * Calculate days until target date
     */
    private function calculateDaysUntil(string $targetDate): int
    {
        $target = new \DateTimeImmutable($targetDate);
        $now = new \DateTimeImmutable();
        $diff = $now->diff($target);
        
        return $diff->invert ? 0 : $diff->days;
    }

    /**
     * Check if current time is weekend or holiday
     */
    private function isWeekendOrHoliday(): bool
    {
        $now = new \DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N'); // 1=Monday, 7=Sunday
        
        // Simple weekend check (Saturday=6, Sunday=7)
        // TODO: Add holiday calendar integration
        return $dayOfWeek >= 6;
    }

    /**
     * Calculate urgency score based on delivery timeline vs approval duration
     * 
     * @param int $daysUntilDelivery Days until required delivery
     * @param float $avgApprovalDays Historical average approval duration
     * @return float Urgency score (0.0 to 1.0, higher = more urgent)
     */
    private function calculateUrgencyScore(int $daysUntilDelivery, float $avgApprovalDays): float
    {
        if ($daysUntilDelivery >= 999) {
            return 0.0; // No delivery date specified
        }

        if ($avgApprovalDays <= 0) {
            $avgApprovalDays = 5.0; // Default assumption
        }

        // Calculate buffer ratio (how many days buffer vs needed)
        $bufferRatio = $daysUntilDelivery / $avgApprovalDays;

        // Convert to urgency score (inverse relationship)
        // bufferRatio >= 2.0 => urgency = 0.0 (plenty of time)
        // bufferRatio = 1.0 => urgency = 0.5 (tight)
        // bufferRatio < 1.0 => urgency = 1.0 (critical)
        if ($bufferRatio >= 2.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, 1.0 - ($bufferRatio / 2.0)));
    }

    /**
     * Calculate value concentration using Gini coefficient
     * Higher value = more concentrated (single high-value line)
     * 
     * @param object $requisition Requisition with lines array
     * @return float Gini coefficient (0.0 to 1.0)
     */
    private function calculateValueConcentration(object $requisition): float
    {
        $lines = $requisition->lines ?? [];
        
        if (count($lines) <= 1) {
            return 1.0; // Single line = maximum concentration
        }

        $values = array_map(fn($line) => (float)($line->lineTotal ?? 0.0), $lines);
        sort($values);

        $n = count($values);
        $sum = array_sum($values);

        if ($sum <= 0) {
            return 0.0;
        }

        $numerator = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($i + 1) * $values[$i];
        }

        $gini = (2 * $numerator) / ($n * $sum) - ($n + 1) / $n;

        return max(0.0, min(1.0, $gini));
    }
}
