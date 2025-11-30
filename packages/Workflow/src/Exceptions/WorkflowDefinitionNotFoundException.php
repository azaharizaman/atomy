<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when a workflow definition is not found.
 */
class WorkflowDefinitionNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Workflow definition with ID '{$id}' not found.");
    }

    public static function withName(string $name): self
    {
        return new self("Workflow definition '{$name}' not found.");
    }
}
