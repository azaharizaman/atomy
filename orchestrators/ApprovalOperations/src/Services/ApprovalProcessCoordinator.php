<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Services;

use Nexus\ApprovalOperations\Contracts\ApprovalInstancePersistInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\Contracts\OperationalWorkflowBridgeInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;
use Nexus\ApprovalOperations\DTOs\RecordApprovalDecisionCommand;
use Nexus\ApprovalOperations\DTOs\StartOperationalApprovalCommand;
use Nexus\ApprovalOperations\DTOs\StartedOperationalApprovalResult;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalDeniedException;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalNotFoundException;
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
        private ApprovalTemplateResolver $templateResolver,
        private ApprovalInstancePersistInterface $instancesPersist,
        private ApprovalInstanceQueryInterface $instancesQuery,
        private PolicyEngineInterface $policyEngine,
        private OperationalWorkflowBridgeInterface $workflowBridge,
        private UlidInterface $ulid,
    ) {
    }

    public function start(StartOperationalApprovalCommand $command): StartedOperationalApprovalResult
    {
        $template = $this->templateResolver->resolve($command->tenantId, $command->subject);

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

        $context = \array_merge($command->context, [
            'templateId' => $template->id,
            'initiatorPrincipalId' => $command->initiatorPrincipalId,
        ]);

        $workflowInstanceId = $this->workflowBridge->startWorkflow(
            $command->tenantId,
            $template->workflowDefinitionId,
            $command->subject,
            $context,
        );

        $instanceId = $this->ulid->generate();
        $instance = new ApprovalInstanceReadModel(
            id: $instanceId,
            tenantId: $command->tenantId,
            templateId: $template->id,
            workflowInstanceId: $workflowInstanceId,
            subject: $command->subject,
            status: 'pending',
        );
        $this->instancesPersist->save($instance);

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

        $this->workflowBridge->applyDecision(
            $command->tenantId,
            $instance->workflowInstanceId,
            $command->decision,
            $command->actorPrincipalId,
            $command->comment,
        );

        $status = $command->decision === OperationalApprovalDecision::Approve ? 'approved' : 'rejected';
        $updated = new ApprovalInstanceReadModel(
            id: $instance->id,
            tenantId: $instance->tenantId,
            templateId: $instance->templateId,
            workflowInstanceId: $instance->workflowInstanceId,
            subject: $instance->subject,
            status: $status,
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
}
