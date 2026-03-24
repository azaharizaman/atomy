<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Contracts;

use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;

/**
 * Port for starting / driving Workflow instances without coupling L2 to concrete Workflow services.
 */
interface OperationalWorkflowBridgeInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function startWorkflow(
        string $tenantId,
        string $workflowDefinitionId,
        ApprovalSubjectRef $subject,
        array $context,
    ): string;

    public function applyDecision(
        string $tenantId,
        string $workflowInstanceId,
        OperationalApprovalDecision $decision,
        ?string $actorPrincipalId,
        ?string $comment,
    ): void;
}
