<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Enums;

/**
 * Review queue status
 */
enum ReviewStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case ESCALATED = 'escalated';
    case CANCELLED = 'cancelled';
}
