<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

use Nexus\ApprovalOperations\Enums\OperationalApprovalWorkflowMissingReason;

/**
 * Distinguishes a missing workflow row from internal approval/workflow correlation corruption.
 */
final class OperationalApprovalWorkflowMissingException extends \DomainException
{
    public function __construct(
        public readonly OperationalApprovalWorkflowMissingReason $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forInstance(string $instanceId): self
    {
        return new self(
            OperationalApprovalWorkflowMissingReason::InstanceCorrelationMissing,
            \sprintf('Operational approval instance %s has no workflow correlation.', $instanceId),
        );
    }

    public static function forWorkflowInstance(string $workflowInstanceId): self
    {
        return new self(
            OperationalApprovalWorkflowMissingReason::WorkflowInstanceNotFound,
            \sprintf('Operational approval workflow instance %s was not found.', $workflowInstanceId),
        );
    }

    public function isWorkflowInstanceMissing(): bool
    {
        return $this->reason === OperationalApprovalWorkflowMissingReason::WorkflowInstanceNotFound;
    }
}
