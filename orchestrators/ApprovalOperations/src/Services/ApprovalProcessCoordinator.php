<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Services;

use Nexus\ApprovalOperations\Contracts\ApprovalCommentPersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstancePersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalTemplateResolverInterface;
use Nexus\ApprovalOperations\Contracts\OperationalWorkflowBridgeInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;
use Nexus\ApprovalOperations\DTOs\RecordApprovalDecisionCommand;
use Nexus\ApprovalOperations\DTOs\StartOperationalApprovalCommand;
use Nexus\ApprovalOperations\DTOs\StartedOperationalApprovalResult;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;
use Nexus\ApprovalOperations\Enums\ApprovalStatus;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalDeniedException;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalNotFoundException;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalWorkflowMissingException;
use Nexus\Common\Contracts\UlidInterface;
use Nexus\PolicyEngine\Contracts\PolicyEngineInterface;
use Nexus\PolicyEngine\Domain\PolicyRequest;
use Nexus\PolicyEngine\Enums\DecisionOutcome;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\TenantId as PolicyTenantId;

final readonly class ApprovalProcessCoordinator
{
    private const string POLICY_ACTION_START = 'operational_approval.start';

    public function __construct(
        private ApprovalTemplateResolverInterface $templateResolver,
        private ApprovalInstancePersistInterface $instancesPersist,
        private ApprovalInstanceQueryInterface $instancesQuery,
        private PolicyEngineInterface $policyEngine,
        private OperationalWorkflowBridgeInterface $workflowBridge,
        private UlidInterface $ulid,
        private ApprovalCommentPersistInterface $comments,
    ) {
    }

    public function start(StartOperationalApprovalCommand $command): StartedOperationalApprovalResult
    {
        $template = $this->templateResolver->resolve($command->tenantId, $command->subject);
        $dueAt = $this->resolveDueAt($command->context);

        $policyRequest = new PolicyRequest(
            tenantId: new PolicyTenantId($command->tenantId),
            policyId: new PolicyId($template->policyId),
            policyVersion: new PolicyVersion($template->policyVersion),
            action: self::POLICY_ACTION_START,
            subject: [
                'principalId' => $command->initiatorPrincipalId,
                'subjectType' => $command->subject->subjectType,
                'subjectId' => $command->subject->subjectId,
            ],
            resource: [
                'templateId' => $template->id,
                'workflowDefinitionId' => $template->workflowDefinitionId,
            ],
            context: $command->context,
        );

        $decision = $this->policyEngine->evaluate($policyRequest);
        if (!$this->isStartAllowed($decision->outcome)) {
            throw OperationalApprovalDeniedException::fromDecision($decision);
        }

        $instanceId = $this->ulid->generate();

        $contextForWorkflow = \array_merge($command->context, [
            'templateId' => $template->id,
            'initiatorPrincipalId' => $command->initiatorPrincipalId,
            'operationalInstanceId' => $instanceId,
        ]);

        $pending = new ApprovalInstanceReadModel(
            id: $instanceId,
            tenantId: $command->tenantId,
            templateId: $template->id,
            workflowInstanceId: null,
            subject: $command->subject,
            status: ApprovalStatus::PENDING,
            dueAt: $dueAt,
            createdAt: null,
        );
        $this->instancesPersist->save($pending);

        $workflowInstanceId = $this->workflowBridge->startWorkflow(
            $command->tenantId,
            $template->workflowDefinitionId,
            $command->subject,
            $contextForWorkflow,
        );

        $completed = new ApprovalInstanceReadModel(
            id: $instanceId,
            tenantId: $command->tenantId,
            templateId: $template->id,
            workflowInstanceId: $workflowInstanceId,
            subject: $command->subject,
            status: ApprovalStatus::PENDING,
            dueAt: $dueAt,
            createdAt: null,
        );
        $this->instancesPersist->save($completed);

        return new StartedOperationalApprovalResult(
            instanceId: $instanceId,
            workflowInstanceId: $workflowInstanceId,
        );
    }

    public function recordDecision(RecordApprovalDecisionCommand $command): void
    {
        $instance = $this->instancesQuery->findById($command->tenantId, $command->instanceId);
        if ($instance === null) {
            throw OperationalApprovalNotFoundException::forInstance($command->instanceId);
        }

        if ($instance->workflowInstanceId === null || \trim($instance->workflowInstanceId) === '') {
            throw OperationalApprovalWorkflowMissingException::forInstance($instance->id);
        }

        $this->workflowBridge->applyDecision(
            $command->tenantId,
            $instance->workflowInstanceId,
            $command->decision,
            $command->actorPrincipalId,
            $command->comment,
        );

        $comment = $command->comment;
        $attachmentStorageKey = $command->attachmentStorageKey;
        if (($comment !== null && \trim($comment) !== '') || ($attachmentStorageKey !== null && \trim($attachmentStorageKey) !== '')) {
            $this->comments->append(
                $command->tenantId,
                $command->instanceId,
                $command->actorPrincipalId,
                $comment ?? '',
                $attachmentStorageKey,
            );
        }

        $status = $command->decision === OperationalApprovalDecision::Approve ? ApprovalStatus::APPROVED : ApprovalStatus::REJECTED;
        $updated = new ApprovalInstanceReadModel(
            id: $instance->id,
            tenantId: $instance->tenantId,
            templateId: $instance->templateId,
            workflowInstanceId: $instance->workflowInstanceId,
            subject: $instance->subject,
            status: $status,
            createdAt: $instance->createdAt,
        );
        $this->instancesPersist->save($updated);
    }

    private function isStartAllowed(DecisionOutcome $outcome): bool
    {
        return \in_array($outcome, [
            DecisionOutcome::Allow,
            DecisionOutcome::Approve,
            DecisionOutcome::Route,
        ], true);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveDueAt(array $context): \DateTimeImmutable
    {
        $seconds = 172800;
        if (isset($context['sla_seconds']) && \is_numeric($context['sla_seconds'])) {
            $seconds = max(60, (int) $context['sla_seconds']);
        } elseif (isset($context['sla_hours']) && \is_numeric($context['sla_hours'])) {
            $seconds = max(1, (int) $context['sla_hours']) * 3600;
        }

        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->add(new \DateInterval('PT' . $seconds . 'S'));
    }
}
