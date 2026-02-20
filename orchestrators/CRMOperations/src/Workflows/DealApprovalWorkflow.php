<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Workflows;

use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Contracts\OpportunityQueryInterface;
use Nexus\CRM\Contracts\OpportunityPersistInterface;
use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;
use Nexus\CRMOperations\Rules\OpportunityApprovalRule;
use Nexus\CRMOperations\DataProviders\OpportunityContextDataProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Deal Approval Workflow
 * 
 * Multi-step workflow for deal approval process.
 * Handles approval requests, escalations, and deal closing.
 * 
 * @package Nexus\CRMOperations\Workflows
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class DealApprovalWorkflow
{
    /**
     * @param OpportunityQueryInterface $opportunityQuery Opportunity query service
     * @param OpportunityPersistInterface $opportunityPersist Opportunity persistence service
     * @param NotificationProviderInterface $notificationProvider Notification provider
     * @param AnalyticsProviderInterface $analyticsProvider Analytics provider
     * @param OpportunityApprovalRule $approvalRule Approval rule
     * @param OpportunityContextDataProvider $contextProvider Context data provider
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private OpportunityQueryInterface $opportunityQuery,
        private OpportunityPersistInterface $opportunityPersist,
        private NotificationProviderInterface $notificationProvider,
        private AnalyticsProviderInterface $analyticsProvider,
        private OpportunityApprovalRule $approvalRule,
        private OpportunityContextDataProvider $contextProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Request approval for a deal
     * 
     * @param string $opportunityId Opportunity ID
     * @param array<string, mixed> $context Approval context
     * @return ApprovalWorkflowResult Workflow result
     */
    public function requestApproval(string $opportunityId, array $context = []): ApprovalWorkflowResult
    {
        $this->logger?->info('Starting Deal Approval workflow', [
            'opportunity_id' => $opportunityId,
        ]);

        $opportunity = $this->opportunityQuery->findByIdOrFail($opportunityId);
        $fullContext = array_merge(
            $this->contextProvider->getContext($opportunityId),
            $context
        );

        // Evaluate approval requirements
        $approvalResult = $this->approvalRule->evaluate($opportunity, $fullContext);

        if (!$approvalResult->requiresApproval) {
            return new ApprovalWorkflowResult(
                success: true,
                requiresApproval: false,
                approvalLevel: null,
                approvalRequestId: null,
                message: 'No approval required for this deal'
            );
        }

        // Create approval request
        $approvalRequestId = $this->createApprovalRequest(
            $opportunity,
            $approvalResult->approvalLevel,
            $approvalResult->reasons
        );

        // Send notification to approvers
        $this->notifyApprovers($approvalRequestId, $opportunity, $approvalResult);

        // Track analytics
        $this->analyticsProvider->track('deal_approval_requested', [
            'opportunity_id' => $opportunityId,
            'approval_level' => $approvalResult->approvalLevel,
            'value' => $opportunity->getValue(),
        ]);

        return new ApprovalWorkflowResult(
            success: true,
            requiresApproval: true,
            approvalLevel: $approvalResult->approvalLevel,
            approvalRequestId: $approvalRequestId,
            message: sprintf(
                'Approval request created for %s level',
                $approvalResult->approvalLevel
            )
        );
    }

    /**
     * Approve a deal
     * 
     * @param string $approvalRequestId Approval request ID
     * @param string $approverId Approver user ID
     * @param array<string, mixed> $options Approval options
     * @return ApprovalWorkflowResult Workflow result
     */
    public function approve(
        string $approvalRequestId,
        string $approverId,
        array $options = []
    ): ApprovalWorkflowResult {
        $this->logger?->info('Deal approved', [
            'approval_request_id' => $approvalRequestId,
            'approver_id' => $approverId,
        ]);

        // In a real implementation, this would update the approval request
        // and potentially trigger the deal closing workflow

        $this->analyticsProvider->track('deal_approval_granted', [
            'approval_request_id' => $approvalRequestId,
            'approver_id' => $approverId,
        ]);

        return new ApprovalWorkflowResult(
            success: true,
            requiresApproval: false,
            approvalLevel: null,
            approvalRequestId: $approvalRequestId,
            message: 'Deal approved successfully'
        );
    }

    /**
     * Reject a deal
     * 
     * @param string $approvalRequestId Approval request ID
     * @param string $approverId Approver user ID
     * @param string $reason Rejection reason
     * @return ApprovalWorkflowResult Workflow result
     */
    public function reject(
        string $approvalRequestId,
        string $approverId,
        string $reason
    ): ApprovalWorkflowResult {
        $this->logger?->info('Deal rejected', [
            'approval_request_id' => $approvalRequestId,
            'approver_id' => $approverId,
            'reason' => $reason,
        ]);

        $this->analyticsProvider->track('deal_approval_rejected', [
            'approval_request_id' => $approvalRequestId,
            'approver_id' => $approverId,
            'reason' => $reason,
        ]);

        return new ApprovalWorkflowResult(
            success: true,
            requiresApproval: false,
            approvalLevel: null,
            approvalRequestId: $approvalRequestId,
            message: sprintf('Deal rejected: %s', $reason)
        );
    }

    /**
     * Escalate approval request
     * 
     * @param string $approvalRequestId Approval request ID
     * @param string $reason Escalation reason
     * @return ApprovalWorkflowResult Workflow result
     */
    public function escalate(
        string $approvalRequestId,
        string $reason
    ): ApprovalWorkflowResult {
        $this->logger?->info('Deal approval escalated', [
            'approval_request_id' => $approvalRequestId,
            'reason' => $reason,
        ]);

        // Escalate to next level
        $this->notificationProvider->escalate(
            'director',
            'Deal Approval Escalation Required',
            sprintf('Approval request %s has been escalated: %s', $approvalRequestId, $reason),
            ['approval_request_id' => $approvalRequestId]
        );

        return new ApprovalWorkflowResult(
            success: true,
            requiresApproval: true,
            approvalLevel: 'director',
            approvalRequestId: $approvalRequestId,
            message: 'Approval request escalated to director level'
        );
    }

    /**
     * Create approval request
     */
    private function createApprovalRequest(
        OpportunityInterface $opportunity,
        ?string $approvalLevel,
        array $reasons
    ): string {
        // In a real implementation, this would create a persistent approval request
        return sprintf('AR-%s-%s', $opportunity->getId(), uniqid());
    }

    /**
     * Notify approvers
     */
    private function notifyApprovers(
        string $approvalRequestId,
        OpportunityInterface $opportunity,
        $approvalResult
    ): void {
        $level = $approvalResult->approvalLevel;
        
        $role = match ($level) {
            'director' => 'director',
            'manager' => 'sales_manager',
            default => 'team_lead',
        };

        $this->notificationProvider->notifyRole(
            $role,
            'Deal Approval Required',
            sprintf(
                'Deal "%s" requires your approval. Value: %s %s',
                $opportunity->getTitle(),
                number_format($opportunity->getValue() / 100, 2),
                $opportunity->getCurrency()
            ),
            [
                'approval_request_id' => $approvalRequestId,
                'opportunity_id' => $opportunity->getId(),
                'reasons' => $approvalResult->reasons,
            ]
        );
    }
}

/**
 * Approval Workflow Result DTO
 */
final readonly class ApprovalWorkflowResult
{
    /**
     * @param bool $success Whether workflow succeeded
     * @param bool $requiresApproval Whether approval is required
     * @param string|null $approvalLevel Required approval level
     * @param string|null $approvalRequestId Created approval request ID
     * @param string $message Result message
     */
    public function __construct(
        public bool $success,
        public bool $requiresApproval,
        public ?string $approvalLevel,
        public ?string $approvalRequestId,
        public string $message
    ) {}
}