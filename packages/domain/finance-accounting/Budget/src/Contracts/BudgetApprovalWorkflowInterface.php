<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Budget\Enums\ApprovalStatus;

/**
 * Budget Approval Workflow contract
 * 
 * Integration interface for workflow-based budget approval processes.
 */
interface BudgetApprovalWorkflowInterface
{
    /**
     * Request budget override approval
     * 
     * @param string $budgetId Budget identifier
     * @param \Nexus\Uom\ValueObjects\Money $requestedAmount Requested amount
     * @param string $requestorId Requestor user identifier
     * @param string $reason Override reason
     * @param array<string, mixed> $context Additional context
     * @return string Workflow instance identifier
     */
    public function requestBudgetOverrideApproval(
        string $budgetId,
        \Nexus\Uom\ValueObjects\Money $requestedAmount,
        string $requestorId,
        string $reason,
        array $context = []
    ): string;

    /**
     * Request budget reallocation approval
     * 
     * @param string $fromBudgetId Source budget identifier
     * @param string $toBudgetId Target budget identifier
     * @param \Nexus\Uom\ValueObjects\Money $amount Transfer amount
     * @param string $requestorId Requestor user identifier
     * @param string $reason Reallocation reason
     * @return string Workflow instance identifier
     */
    public function requestReallocationApproval(
        string $fromBudgetId,
        string $toBudgetId,
        \Nexus\Uom\ValueObjects\Money $amount,
        string $requestorId,
        string $reason
    ): string;

    /**
     * Request variance investigation response
     * 
     * @param string $budgetId Budget identifier
     * @param float $variancePercentage Variance percentage
     * @param string $managerId Manager identifier
     * @return string Workflow instance identifier
     */
    public function requestInvestigationResponse(
        string $budgetId,
        float $variancePercentage,
        string $managerId
    ): string;

    /**
     * Request budget rollover approval
     * 
     * @param string $budgetId Budget identifier
     * @param \Nexus\Uom\ValueObjects\Money $carryoverAmount Amount to carry over
     * @param string $nextPeriodId Next period identifier
     * @return string Workflow instance identifier
     */
    public function requestRolloverApproval(
        string $budgetId,
        \Nexus\Uom\ValueObjects\Money $carryoverAmount,
        string $nextPeriodId
    ): string;

    /**
     * Request budget amendment approval
     * 
     * @param string $budgetId Budget identifier
     * @param \Nexus\Uom\ValueObjects\Money $currentAmount Current amount
     * @param \Nexus\Uom\ValueObjects\Money $newAmount New amount
     * @param string $requestorId Requestor user identifier
     * @param string $reason Amendment reason
     * @return string Workflow instance identifier
     */
    public function requestAmendmentApproval(
        string $budgetId,
        \Nexus\Uom\ValueObjects\Money $currentAmount,
        \Nexus\Uom\ValueObjects\Money $newAmount,
        string $requestorId,
        string $reason
    ): string;

    /**
     * Check approval status
     * 
     * @param string $workflowId Workflow instance identifier
     * @return ApprovalStatus
     */
    public function checkApprovalStatus(string $workflowId): ApprovalStatus;
}
