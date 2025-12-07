<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows;

use Nexus\ProcurementOperations\Contracts\SagaInterface;
use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\Contracts\WorkflowStorageInterface;
use Nexus\ProcurementOperations\DTOs\SagaContext;
use Nexus\ProcurementOperations\DTOs\SagaResult;
use Nexus\ProcurementOperations\Events\RequisitionApprovalStartedEvent;
use Nexus\ProcurementOperations\Events\RequisitionApprovalCompletedEvent;
use Nexus\ProcurementOperations\Events\RequisitionApprovalRejectedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Requisition Approval Workflow.
 *
 * Manages the approval process for purchase requisitions with configurable
 * approval levels based on amount thresholds, categories, and departmental rules.
 *
 * Workflow Steps:
 * 1. Validate requisition data and budget availability
 * 2. Route to appropriate approver(s) based on rules
 * 3. Wait for approval response (approve/reject/delegate)
 * 4. Apply approval or trigger next approval level
 * 5. Notify requestor of outcome
 *
 * Supports:
 * - Multi-level approval chains
 * - Parallel approvals (all must approve)
 * - Sequential approvals (one at a time)
 * - Amount-based routing
 * - Department-based routing
 * - Delegation and escalation
 */
final class RequisitionApprovalWorkflow extends AbstractSaga implements SagaInterface
{
    private const WORKFLOW_ID = 'requisition_approval_workflow';

    /**
     * @param array<SagaStepInterface> $steps
     */
    public function __construct(
        private readonly array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($storage, $eventDispatcher, $logger ?? new NullLogger());
    }

    /**
     * Create workflow with default steps.
     *
     * @param array<SagaStepInterface> $steps
     */
    public static function create(
        array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ): self {
        return new self(
            steps: $steps,
            storage: $storage,
            eventDispatcher: $eventDispatcher,
            logger: $logger,
        );
    }

    public function getId(): string
    {
        return self::WORKFLOW_ID;
    }

    public function getName(): string
    {
        return 'Requisition Approval Workflow';
    }

    public function getDescription(): string
    {
        return 'Multi-level approval workflow for purchase requisitions with configurable routing rules';
    }

    /**
     * @return array<SagaStepInterface>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Execute the approval workflow.
     */
    public function execute(SagaContext $context): SagaResult
    {
        $requisitionId = $context->data['requisitionId'] ?? null;
        $amountCents = $context->data['totalAmountCents'] ?? 0;
        $requestorId = $context->userId;

        // Dispatch workflow started event
        $this->eventDispatcher->dispatch(new RequisitionApprovalStartedEvent(
            tenantId: $context->tenantId,
            requisitionId: $requisitionId,
            amountCents: $amountCents,
            requestorId: $requestorId,
        ));

        // Execute base saga logic
        $result = parent::execute($context);

        // Dispatch completion/rejection event based on result
        if ($result->success) {
            $this->eventDispatcher->dispatch(new RequisitionApprovalCompletedEvent(
                tenantId: $context->tenantId,
                requisitionId: $requisitionId,
                approvedBy: $result->data['approvedBy'] ?? [],
                approvedAt: new \DateTimeImmutable(),
            ));
        } else {
            $this->eventDispatcher->dispatch(new RequisitionApprovalRejectedEvent(
                tenantId: $context->tenantId,
                requisitionId: $requisitionId,
                rejectedBy: $result->data['rejectedBy'] ?? null,
                reason: $result->errorMessage,
            ));
        }

        return $result;
    }

    /**
     * Submit a requisition for approval.
     *
     * @param array<string, mixed> $requisitionData
     */
    public function submitForApproval(
        string $tenantId,
        string $requisitionId,
        string $requestorId,
        array $requisitionData,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $requestorId,
            data: array_merge($requisitionData, [
                'requisitionId' => $requisitionId,
                'action' => 'submit',
            ]),
            metadata: ['submittedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601)],
        );

        return $this->execute($context);
    }

    /**
     * Process an approval decision.
     */
    public function processApprovalDecision(
        string $tenantId,
        string $requisitionId,
        string $approverId,
        string $decision,
        ?string $comments = null,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $approverId,
            data: [
                'requisitionId' => $requisitionId,
                'action' => 'decision',
                'decision' => $decision,
                'comments' => $comments,
            ],
            metadata: ['decidedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601)],
        );

        // Resume from pending approval step
        return $this->resume($requisitionId, $context->data);
    }

    /**
     * Delegate approval to another user.
     */
    public function delegateApproval(
        string $tenantId,
        string $requisitionId,
        string $delegatorId,
        string $delegateeId,
        ?string $reason = null,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $delegatorId,
            data: [
                'requisitionId' => $requisitionId,
                'action' => 'delegate',
                'delegateeId' => $delegateeId,
                'reason' => $reason,
            ],
            metadata: ['delegatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601)],
        );

        return $this->resume($requisitionId, $context->data);
    }

    /**
     * Escalate a pending approval.
     */
    public function escalateApproval(
        string $tenantId,
        string $requisitionId,
        string $escalatorId,
        string $reason,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $escalatorId,
            data: [
                'requisitionId' => $requisitionId,
                'action' => 'escalate',
                'reason' => $reason,
            ],
            metadata: ['escalatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601)],
        );

        return $this->resume($requisitionId, $context->data);
    }

    /**
     * Cancel a pending approval.
     */
    public function cancelApproval(
        string $tenantId,
        string $requisitionId,
        string $cancelledBy,
        string $reason,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $cancelledBy,
            data: [
                'requisitionId' => $requisitionId,
                'action' => 'cancel',
                'reason' => $reason,
            ],
            metadata: ['cancelledAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601)],
        );

        return $this->cancel($requisitionId, $context->data);
    }

    /**
     * Get approval status for a requisition.
     *
     * @return array{
     *     status: string,
     *     currentStep: ?string,
     *     pendingApprovers: array<string>,
     *     completedApprovals: array<array{approverId: string, decision: string, timestamp: string}>,
     *     nextApprovers: array<string>
     * }
     */
    public function getApprovalStatus(string $tenantId, string $requisitionId): array
    {
        $state = $this->getState($requisitionId);

        if ($state === null) {
            return [
                'status' => 'not_found',
                'currentStep' => null,
                'pendingApprovers' => [],
                'completedApprovals' => [],
                'nextApprovers' => [],
            ];
        }

        return [
            'status' => $state->getStatus()->value,
            'currentStep' => $state->getCurrentStepId(),
            'pendingApprovers' => $state->getData()['pendingApprovers'] ?? [],
            'completedApprovals' => $state->getData()['completedApprovals'] ?? [],
            'nextApprovers' => $this->determineNextApprovers($state),
        ];
    }

    /**
     * Determine next approvers based on current state.
     *
     * @return array<string>
     */
    private function determineNextApprovers(mixed $state): array
    {
        // In a real implementation, this would evaluate approval rules
        // based on amount, category, department, etc.
        return [];
    }
}
