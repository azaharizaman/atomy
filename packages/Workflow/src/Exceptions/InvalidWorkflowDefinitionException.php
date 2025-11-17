<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when a workflow definition is invalid.
 */
class InvalidWorkflowDefinitionException extends \RuntimeException
{
    public static function missingInitialState(): self
    {
        return new self("Workflow definition must have an initial state.");
    }

    public static function duplicateState(string $state): self
    {
        return new self("Duplicate state '{$state}' in workflow definition.");
    }

    public static function invalidTransition(string $transition): self
    {
        return new self("Invalid transition '{$transition}' in workflow definition.");
    }

    public static function missingState(string $state): self
    {
        return new self("State '{$state}' referenced but not defined.");
    }
}
