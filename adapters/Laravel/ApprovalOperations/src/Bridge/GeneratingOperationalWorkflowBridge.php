<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Bridge;

use Illuminate\Support\Str;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalWorkflowMissingException;
use Nexus\ApprovalOperations\Contracts\OperationalWorkflowBridgeInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalWorkflow;

/**
 * Operational approval workflow bridge backed by the local workflow table.
 */
final readonly class GeneratingOperationalWorkflowBridge implements OperationalWorkflowBridgeInterface
{
    public function startWorkflow(
        string $tenantId,
        string $workflowDefinitionId,
        ApprovalSubjectRef $subject,
        array $context,
    ): string {
        $workflowId = (string) Str::ulid();
        $instanceId = (string) ($context['operationalInstanceId'] ?? '');

        OperationalApprovalWorkflow::query()->create([
            'id' => $workflowId,
            'tenant_id' => $tenantId,
            'operational_approval_instance_id' => $instanceId,
            'workflow_definition_id' => $workflowDefinitionId,
            'subject_type' => $subject->subjectType,
            'subject_id' => $subject->subjectId,
            'current_state' => 'pending',
            'last_actor_principal_id' => null,
            'last_comment' => null,
        ]);

        return $workflowId;
    }

    public function applyDecision(
        string $tenantId,
        string $workflowInstanceId,
        OperationalApprovalDecision $decision,
        ?string $actorPrincipalId,
        ?string $comment,
    ): void {
        $currentState = $decision === OperationalApprovalDecision::Approve ? 'approved' : 'rejected';

        $updated = OperationalApprovalWorkflow::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $workflowInstanceId)
            ->update([
                'current_state' => $currentState,
                'last_actor_principal_id' => $actorPrincipalId,
                'last_comment' => $comment,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            throw OperationalApprovalWorkflowMissingException::forWorkflowInstance($workflowInstanceId);
        }
    }
}
