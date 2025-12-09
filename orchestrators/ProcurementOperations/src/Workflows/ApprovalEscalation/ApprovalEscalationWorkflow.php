<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows\ApprovalEscalation;

use Nexus\ProcurementOperations\Contracts\SagaInterface;
use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\Contracts\WorkflowStorageInterface;
use Nexus\ProcurementOperations\DTOs\SagaContext;
use Nexus\ProcurementOperations\DTOs\SagaResult;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\ProcurementOperations\Events\ApprovalEscalatedEvent;
use Nexus\ProcurementOperations\Workflows\AbstractSaga;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Approval Escalation Workflow.
 *
 * Handles automatic escalation of pending approvals when:
 * - SLA timeout is exceeded (configurable, default 48 hours)
 * - Approver has not responded within the timeout period
 *
 * Escalation Process:
 * 1. Check for pending approvals exceeding timeout
 * 2. Identify next level approver
 * 3. Reassign approval task to escalated approver
 * 4. Notify original approver and escalated approver
 * 5. Log escalation in audit trail
 *
 * This workflow is typically triggered by a scheduled job that runs
 * periodically (e.g., every hour) to check for timed-out approvals.
 */
final class ApprovalEscalationWorkflow extends AbstractSaga implements SagaInterface
{
    private const WORKFLOW_ID = 'approval_escalation_workflow';

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
        return 'Approval Escalation Workflow';
    }

    public function getDescription(): string
    {
        return 'Automatic escalation of pending approvals when SLA timeout is exceeded';
    }

    /**
     * @return array<SagaStepInterface>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Execute the escalation workflow for a specific pending approval.
     */
    public function execute(SagaContext $context): SagaResult
    {
        $documentId = $context->data['documentId'] ?? null;
        $documentType = $context->data['documentType'] ?? null;
        $currentLevel = $context->data['currentLevel'] ?? 1;
        $originalApproverId = $context->data['originalApproverId'] ?? null;
        $timeoutHours = $context->data['timeoutHours'] ?? 48;

        $this->logger->info('Executing approval escalation', [
            'tenant_id' => $context->tenantId,
            'document_id' => $documentId,
            'document_type' => $documentType,
            'current_level' => $currentLevel,
        ]);

        // Execute base saga logic
        $result = parent::execute($context);

        // If escalation was successful, dispatch event
        if ($result->success) {
            $fromLevel = ApprovalLevel::from($currentLevel);
            $toLevel = $fromLevel->nextLevel() ?? $fromLevel;

            $this->eventDispatcher->dispatch(new ApprovalEscalatedEvent(
                tenantId: $context->tenantId,
                documentId: $documentId,
                documentType: $documentType,
                fromLevel: $fromLevel,
                toLevel: $toLevel,
                originalApproverId: $originalApproverId,
                escalatedToApproverId: $result->data['escalatedToApproverId'] ?? '',
                timeoutHours: $timeoutHours,
                escalatedAt: new \DateTimeImmutable(),
            ));
        }

        return $result;
    }

    /**
     * Process escalation for a pending approval.
     *
     * @param string $tenantId Tenant context
     * @param string $documentId Document ID (requisition or PO)
     * @param string $documentType Document type
     * @param int $currentLevel Current approval level
     * @param string $originalApproverId Original approver who timed out
     * @param string $escalatedToApproverId New approver after escalation
     * @param int $timeoutHours Configured timeout hours
     */
    public function processEscalation(
        string $tenantId,
        string $documentId,
        string $documentType,
        int $currentLevel,
        string $originalApproverId,
        string $escalatedToApproverId,
        int $timeoutHours
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: 'system', // Escalation is triggered by system
            data: [
                'documentId' => $documentId,
                'documentType' => $documentType,
                'currentLevel' => $currentLevel,
                'originalApproverId' => $originalApproverId,
                'escalatedToApproverId' => $escalatedToApproverId,
                'timeoutHours' => $timeoutHours,
                'action' => 'escalate',
            ],
            metadata: [
                'escalatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'reason' => 'SLA timeout exceeded',
            ],
        );

        return $this->execute($context);
    }

    /**
     * Check if an approval should be escalated.
     *
     * @param \DateTimeImmutable $assignedAt When approval was assigned
     * @param int $timeoutHours Configured timeout hours
     */
    public function shouldEscalate(\DateTimeImmutable $assignedAt, int $timeoutHours): bool
    {
        $now = new \DateTimeImmutable();
        $timeoutDate = $assignedAt->modify("+{$timeoutHours} hours");

        return $now > $timeoutDate;
    }

    /**
     * Get the next escalation level.
     *
     * Returns null if already at maximum level.
     */
    public function getNextEscalationLevel(int $currentLevel): ?ApprovalLevel
    {
        $current = ApprovalLevel::tryFrom($currentLevel);

        if ($current === null) {
            return ApprovalLevel::LEVEL_1;
        }

        return $current->nextLevel();
    }
}
