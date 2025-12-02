<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Enums;

/**
 * Period close status.
 */
enum CloseStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';
    case LOCKED = 'locked';
}
