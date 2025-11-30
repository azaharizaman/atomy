<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when a state is not found.
 */
class StateNotFoundException extends \RuntimeException
{
    public static function withName(string $name): self
    {
        return new self("State '{$name}' not found in workflow definition.");
    }
}
