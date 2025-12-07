<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for workflow-related errors.
 */
class WorkflowException extends ProcurementOperationsException
{
    /**
     * Create exception for workflow not found.
     */
    public static function notFound(string $workflowInstanceId): self
    {
        return new self(
            sprintf('Workflow instance not found: %s', $workflowInstanceId)
        );
    }

    /**
     * Create exception for workflow initiation failure.
     */
    public static function initiationFailed(string $workflowType, string $reason): self
    {
        return new self(
            sprintf('Failed to initiate workflow %s: %s', $workflowType, $reason)
        );
    }

    /**
     * Create exception for invalid workflow transition.
     */
    public static function invalidTransition(
        string $workflowInstanceId,
        string $currentState,
        string $attemptedState
    ): self {
        return new self(
            sprintf(
                'Invalid workflow transition for %s: Cannot go from "%s" to "%s"',
                $workflowInstanceId,
                $currentState,
                $attemptedState
            )
        );
    }

    /**
     * Create exception for workflow completion failure.
     */
    public static function completionFailed(string $workflowInstanceId, string $reason): self
    {
        return new self(
            sprintf('Failed to complete workflow %s: %s', $workflowInstanceId, $reason)
        );
    }
}
