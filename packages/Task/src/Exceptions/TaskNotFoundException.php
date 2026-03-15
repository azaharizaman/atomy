<?php

declare(strict_types=1);

namespace Nexus\Task\Exceptions;

final class TaskNotFoundException extends TaskException
{
    public static function forId(string $taskId): self
    {
        return new self(sprintf('Task not found: %s', $taskId));
    }
}
