<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when circular dependency detected in workflow definition.
 */
class CircularDependencyException extends \RuntimeException
{
    public static function inStates(array $states): self
    {
        $stateList = implode(' -> ', $states);
        return new self("Circular dependency detected in state chain: {$stateList}");
    }
}
