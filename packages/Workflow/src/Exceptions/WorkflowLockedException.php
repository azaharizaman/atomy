<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when workflow is locked and cannot be modified.
 */
class WorkflowLockedException extends \RuntimeException
{
    public static function forWorkflow(string $workflowId): self
    {
        return new self("Workflow '{$workflowId}' is locked and cannot be modified.");
    }
}
