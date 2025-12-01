<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Exceptions;

/**
 * Exception thrown when a workflow execution fails
 */
class WorkflowException extends \Exception
{
    /**
     * Create a new workflow exception
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable
     */
    public function __construct(
        string $message = 'Workflow execution failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for validation failure
     */
    public static function validationFailed(string $workflowName, string $reason): self
    {
        return new self(
            sprintf('Workflow "%s" validation failed: %s', $workflowName, $reason)
        );
    }

    /**
     * Create exception for step failure
     */
    public static function stepFailed(string $workflowName, string $stepName, string $reason): self
    {
        return new self(
            sprintf('Workflow "%s" failed at step "%s": %s', $workflowName, $stepName, $reason)
        );
    }

    /**
     * Create exception for timeout
     */
    public static function timeout(string $workflowName, int $timeoutSeconds): self
    {
        return new self(
            sprintf('Workflow "%s" timed out after %d seconds', $workflowName, $timeoutSeconds)
        );
    }

    /**
     * Create exception for invalid state transition
     */
    public static function invalidStateTransition(string $workflowName, string $fromState, string $toState): self
    {
        return new self(
            sprintf(
                'Invalid state transition in workflow "%s": cannot transition from "%s" to "%s"',
                $workflowName,
                $fromState,
                $toState
            )
        );
    }

    /**
     * Create exception for missing dependency
     */
    public static function missingDependency(string $workflowName, string $dependencyName): self
    {
        return new self(
            sprintf('Workflow "%s" is missing required dependency: %s', $workflowName, $dependencyName)
        );
    }

    /**
     * Create exception for cancelled workflow
     */
    public static function cancelled(string $workflowName, string $reason = ''): self
    {
        $message = sprintf('Workflow "%s" was cancelled', $workflowName);
        if ($reason !== '') {
            $message .= ': ' . $reason;
        }
        return new self($message);
    }
}
