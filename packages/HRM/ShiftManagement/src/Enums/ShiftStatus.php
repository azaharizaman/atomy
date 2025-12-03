<?php

declare(strict_types=1);

namespace Nexus\ShiftManagement\Enums;

enum ShiftStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
