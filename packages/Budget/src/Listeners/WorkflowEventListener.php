<?php

declare(strict_types=1);

namespace Nexus\Budget\Listeners;

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\ApprovalCompletedEventInterface;
use Nexus\Budget\Contracts\ApprovalRejectedEventInterface;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\Enums\VarianceInvestigationStatus;
use Nexus\Budget\Services\BudgetVarianceInvestigator;
use Nexus\Budget\Services\BudgetRolloverHandler;
use Nexus\Budget\ValueObjects\BudgetAllocation;
use Psr\Log\LoggerInterface;

/**
 * Workflow Event Listener
 * 
 * Listens to Workflow package events to handle budget approval flows.
 * - Budget Override Approved: Allow commitment to proceed
 * - Budget Rollover Approved: Create next period budget
 * - Variance Investigation Resolved: Update budget status
 */
final readonly class WorkflowEventListener
{
    public function __construct(
        private BudgetManagerInterface $budgetManager,
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetVarianceInvestigator $varianceInvestigator,
        private BudgetRolloverHandler $rolloverHandler,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle approval completed event
     */
    public function onApprovalCompleted(ApprovalCompletedEventInterface $event): void
    {
        try {
            // Route to appropriate handler based on workflow type
            match ($event->getWorkflowType()) {
                'budget_override' => $this->handleBudgetOverrideApproval($event),
                'budget_rollover' => $this->handleRolloverApproval($event),
                'variance_investigation' => $this->handleVarianceInvestigationApproval($event),
                'budget_creation' => $this->handleBudgetCreationApproval($event),
                'budget_amendment' => $this->handleBudgetAmendmentApproval($event),
                default => $this->logger->warning('Unknown workflow type', [
                    'workflow_type' => $event->getWorkflowType(),
                    'entity_id' => $event->getEntityId(),
                ]),
            };
        } catch (\Exception $e) {
            $this->logger->error('Failed to process workflow approval', [
                'workflow_type' => $event->getWorkflowType(),
                'entity_id' => $event->getEntityId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle approval rejected event
     */
    public function onApprovalRejected(ApprovalRejectedEventInterface $event): void
    {
        try {
            // Route to appropriate handler based on workflow type
            match ($event->getWorkflowType()) {
                'budget_override' => $this->handleBudgetOverrideRejection($event),
                'budget_rollover' => $this->handleRolloverRejection($event),
                'variance_investigation' => $this->handleVarianceInvestigationRejection($event),
                'budget_creation' => $this->handleBudgetCreationRejection($event),
                default => $this->logger->warning('Unknown workflow type for rejection', [
                    'workflow_type' => $event->getWorkflowType(),
                    'entity_id' => $event->getEntityId(),
                ]),
            };
        } catch (\Exception $e) {
            $this->logger->error('Failed to process workflow rejection', [
                'workflow_type' => $event->getWorkflowType(),
                'entity_id' => $event->getEntityId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle budget override approval - allow the over-budget commitment
     */
    private function handleBudgetOverrideApproval(ApprovalCompletedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // In real implementation, would:
        // 1. Retrieve the pending commitment details from workflow context
        // 2. Process the commitment that was blocked
        // 3. Update budget status if needed
        
        $this->logger->info('Budget override approved', [
            'budget_id' => $budgetId,
            'approved_by' => $event->getApprovedBy(),
        ]);
    }

    /**
     * Handle budget override rejection
     */
    private function handleBudgetOverrideRejection(ApprovalRejectedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        $this->logger->info('Budget override rejected', [
            'budget_id' => $budgetId,
            'rejected_by' => $event->getRejectedBy(),
            'reason' => $event->getReason(),
        ]);
    }

    /**
     * Handle rollover approval - create new budget
     */
    private function handleRolloverApproval(ApprovalCompletedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // In real implementation, would:
        // 1. Retrieve rollover details from workflow context
        // 2. Create new budget in next period with approved amount
        
        $this->logger->info('Budget rollover approved', [
            'budget_id' => $budgetId,
            'approved_by' => $event->getApprovedBy(),
        ]);
    }

    /**
     * Handle rollover rejection
     */
    private function handleRolloverRejection(ApprovalRejectedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // Mark original budget as closed (no rollover)
        $this->budgetRepository->updateStatus($budgetId, BudgetStatus::Closed);
        
        $this->logger->info('Budget rollover rejected - budget closed', [
            'budget_id' => $budgetId,
            'rejected_by' => $event->getRejectedBy(),
        ]);
    }

    /**
     * Handle variance investigation approval
     */
    private function handleVarianceInvestigationApproval(ApprovalCompletedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // Resolve investigation as approved
        $this->varianceInvestigator->resolveInvestigation(
            $budgetId,
            VarianceInvestigationStatus::Approved,
            $event->getNotes() ?? 'Variance approved by management'
        );
        
        $this->logger->info('Variance investigation approved', [
            'budget_id' => $budgetId,
            'approved_by' => $event->getApprovedBy(),
        ]);
    }

    /**
     * Handle variance investigation rejection
     */
    private function handleVarianceInvestigationRejection(ApprovalRejectedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // Resolve investigation as rejected (lock budget)
        $this->varianceInvestigator->resolveInvestigation(
            $budgetId,
            VarianceInvestigationStatus::Rejected,
            $event->getReason() ?? 'Variance rejected - budget locked'
        );
        
        $this->logger->info('Variance investigation rejected - budget locked', [
            'budget_id' => $budgetId,
            'rejected_by' => $event->getRejectedBy(),
        ]);
    }

    /**
     * Handle budget creation approval
     */
    private function handleBudgetCreationApproval(ApprovalCompletedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // Update status from Draft to Approved
        $this->budgetRepository->updateStatus($budgetId, BudgetStatus::Approved);
        
        $this->logger->info('Budget creation approved', [
            'budget_id' => $budgetId,
            'approved_by' => $event->getApprovedBy(),
        ]);
    }

    /**
     * Handle budget creation rejection
     */
    private function handleBudgetCreationRejection(ApprovalRejectedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // Could either delete or mark as rejected
        // For audit trail, we'll keep it as Draft with note
        
        $this->logger->info('Budget creation rejected', [
            'budget_id' => $budgetId,
            'rejected_by' => $event->getRejectedBy(),
            'reason' => $event->getReason(),
        ]);
    }

    /**
     * Handle budget amendment approval
     */
    private function handleBudgetAmendmentApproval(ApprovalCompletedEventInterface $event): void
    {
        $budgetId = $event->getEntityId();
        
        // In real implementation, would:
        // 1. Retrieve amendment details from workflow context
        // 2. Apply the approved changes to budget
        
        $this->logger->info('Budget amendment approved', [
            'budget_id' => $budgetId,
            'approved_by' => $event->getApprovedBy(),
        ]);
    }
}
