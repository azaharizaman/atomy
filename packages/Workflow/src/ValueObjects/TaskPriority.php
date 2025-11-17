<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Task priority enum.
 */
enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}
