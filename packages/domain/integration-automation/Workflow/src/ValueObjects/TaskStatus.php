<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Task status enum.
 */
enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
