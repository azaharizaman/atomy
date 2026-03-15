<?php

declare(strict_types=1);

namespace Nexus\Project\Exceptions;

/**
 * Thrown when project status cannot be set to completed (BUS-PRO-0096).
 */
final class ProjectCompletionException extends ProjectException
{
    public static function hasIncompleteTasks(string $projectId, int $count): self
    {
        return new self(sprintf(
            'Project status cannot be completed while there are %d incomplete task(s): %s.',
            $count,
            $projectId
        ));
    }
}
