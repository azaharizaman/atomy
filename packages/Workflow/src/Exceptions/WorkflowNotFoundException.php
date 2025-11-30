<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when a workflow is not found.
 */
class WorkflowNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Workflow with ID '{$id}' not found.");
    }

    public static function forSubject(string $subjectType, string $subjectId): self
    {
        return new self("No workflow found for subject {$subjectType}:{$subjectId}.");
    }
}
