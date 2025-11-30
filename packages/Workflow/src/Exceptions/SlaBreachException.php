<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when SLA deadline is breached.
 */
class SlaBreachException extends \RuntimeException
{
    public static function forWorkflow(string $workflowId): self
    {
        return new self("SLA breached for workflow '{$workflowId}'.");
    }
}
