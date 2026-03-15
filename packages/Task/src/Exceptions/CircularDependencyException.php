<?php

declare(strict_types=1);

namespace Nexus\Task\Exceptions;

/**
 * Thrown when task dependencies form a cycle (BUS-PRO-0090).
 */
final class CircularDependencyException extends TaskException
{
    public static function new(): self
    {
        return new self('Task dependencies must not create circular references.');
    }
}
