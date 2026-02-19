<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Exceptions;

/**
 * Workflow Execution Exception
 * 
 * Thrown when workflow execution fails.
 * 
 * @package Nexus\CRMOperations\Exceptions
 */
final class WorkflowExecutionException extends CRMOperationsException
{
    /**
     * Create for step failure
     */
    public static function stepFailed(
        string $workflowType,
        string $step,
        string $reason
    ): self {
        return new self(
            message: "Workflow '{$workflowType}' failed at step '{$step}': {$reason}",
            context: "workflow: {$workflowType}, step: {$step}"
        );
    }

    /**
     * Create for timeout
     */
    public static function timeout(string $workflowType, string $step): self
    {
        return new self(
            message: "Workflow '{$workflowType}' timed out at step '{$step}'",
            context: "workflow: {$workflowType}, step: {$step}"
        );
    }

    /**
     * Create for invalid state
     */
    public static function invalidState(
        string $workflowType,
        string $currentState,
        string $expectedState
    ): self {
        return new self(
            message: "Workflow '{$workflowType}' in invalid state '{$currentState}', expected '{$expectedState}'",
            context: "workflow: {$workflowType}, current: {$currentState}, expected: {$expectedState}"
        );
    }
}
