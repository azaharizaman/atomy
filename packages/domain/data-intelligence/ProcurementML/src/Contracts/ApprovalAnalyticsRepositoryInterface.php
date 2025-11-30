<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

/**
 * Approval analytics repository interface for risk prediction
 * 
 * Provides analytical queries for approval workflow analysis,
 * budget tracking, and historical performance metrics.
 */
interface ApprovalAnalyticsRepositoryInterface
{
    /**
     * Get requester's historical approval rate
     * 
     * @param string $requesterId Requester identifier
     * @return float Approval rate (0.0 to 1.0, e.g., 0.85 = 85% approved)
     */
    public function getRequesterApprovalRate(string $requesterId): float;

    /**
     * Get requester's average approval duration
     * 
     * @param string $requesterId Requester identifier
     * @return float Average days from submission to final approval
     */
    public function getRequesterAvgApprovalDuration(string $requesterId): float;

    /**
     * Get requester's top rejection reasons
     * 
     * @param string $requesterId Requester identifier
     * @return array<array{reason: string, count: int}> Top rejection reasons
     */
    public function getRequesterTopRejectionReasons(string $requesterId): array;

    /**
     * Get department's budget utilization percentage
     * 
     * @param string $departmentId Department identifier
     * @return float Utilization (0.0 to 1.0+, can exceed 1.0 if over budget)
     */
    public function getDepartmentBudgetUtilization(string $departmentId): float;

    /**
     * Get department's spending velocity
     * 
     * @param string $departmentId Department identifier
     * @return float Average spending per day (in base currency)
     */
    public function getDepartmentSpendingVelocity(string $departmentId): float;

    /**
     * Get department's remaining budget
     * 
     * @param string $departmentId Department identifier
     * @return float Remaining budget amount (can be negative if over budget)
     */
    public function getDepartmentBudgetRemaining(string $departmentId): float;

    /**
     * Get required approval levels for amount
     * 
     * @param float $amount Requisition amount
     * @param string $departmentId Department identifier
     * @return int Number of approval levels required
     */
    public function getRequiredApprovalLevels(float $amount, string $departmentId): int;

    /**
     * Get primary approver's current workload
     * 
     * @param string $departmentId Department identifier
     * @return int Number of pending approvals for primary approver
     */
    public function getPrimaryApproverWorkload(string $departmentId): int;

    /**
     * Get category's average approval duration
     * 
     * @param string $categoryId Product category identifier
     * @return float Average days to approval for this category
     */
    public function getCategoryAvgApprovalDuration(string $categoryId): float;

    /**
     * Get category's rejection rate
     * 
     * @param string $categoryId Product category identifier
     * @return float Rejection rate (0.0 to 1.0)
     */
    public function getCategoryRejectionRate(string $categoryId): float;

    /**
     * Get requester-approver relationship score
     * Based on historical approval patterns
     * 
     * @param string $requesterId Requester identifier
     * @param string $departmentId Department identifier (to identify approver)
     * @return float Relationship score (0.0 = no history, 1.0 = strong positive history)
     */
    public function getRequesterApproverRelationshipScore(string $requesterId, string $departmentId): float;

    /**
     * Get compliance requirement count for category
     * 
     * @param string $categoryId Product category identifier
     * @return int Number of compliance checks required
     */
    public function getComplianceRequirementCount(string $categoryId): int;

    /**
     * Calculate technical complexity score
     * Based on specification completeness, custom requirements, etc.
     * 
     * @param object $requisition Requisition object
     * @return float Complexity score (0.0 = simple, 1.0 = highly complex)
     */
    public function getTechnicalComplexityScore(object $requisition): float;
}
