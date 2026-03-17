<?php

declare(strict_types=1);

namespace App\Services\Task\Exceptions;

use DomainException;

final class TaskNotFoundException extends DomainException
{
    public function __construct(public readonly string $taskId, public readonly string $tenantId)
    {
        parent::__construct(sprintf('Task not found (task=%s, tenant=%s).', $taskId, $tenantId));
    }
}

