<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when a user attempts unauthorized action on a task.
 */
class UnauthorizedTaskActionException extends \RuntimeException
{
    public static function forUser(string $userId, string $taskId): self
    {
        return new self("User '{$userId}' is not authorized to act on task '{$taskId}'.");
    }

    public static function selfApproval(): self
    {
        return new self("User cannot approve their own submission.");
    }
}
