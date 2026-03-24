<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Bridge;

use Illuminate\Support\Str;
use Nexus\ApprovalOperations\Contracts\OperationalWorkflowBridgeInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;

/**
 * Generates correlation IDs until Workflow is fully wired to repositories.
 */
final readonly class GeneratingOperationalWorkflowBridge implements OperationalWorkflowBridgeInterface
{
    public function startWorkflow(
        string $tenantId,
        string $workflowDefinitionId,
        ApprovalSubjectRef $subject,
        array $context,
    ): string {
        return (string) Str::ulid();
    }

    public function applyDecision(
        string $tenantId,
        string $workflowInstanceId,
        OperationalApprovalDecision $decision,
        ?string $actorPrincipalId,
        ?string $comment,
    ): void {
    }
}
