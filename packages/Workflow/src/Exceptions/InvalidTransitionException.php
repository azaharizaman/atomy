<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when attempting an invalid state transition.
 */
class InvalidTransitionException extends \RuntimeException
{
    public static function fromState(string $transition, string $currentState): self
    {
        return new self("Transition '{$transition}' is not allowed from state '{$currentState}'.");
    }

    public static function notDefined(string $transition): self
    {
        return new self("Transition '{$transition}' is not defined in this workflow.");
    }
}
