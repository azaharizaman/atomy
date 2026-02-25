<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Enums;

enum TicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';
}
