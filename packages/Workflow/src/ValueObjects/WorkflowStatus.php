<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Workflow status enum.
 */
enum WorkflowStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case SUSPENDED = 'suspended';
    case FAILED = 'failed';
}
