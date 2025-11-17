<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when a task is not found.
 */
class TaskNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Task with ID '{$id}' not found.");
    }
}
