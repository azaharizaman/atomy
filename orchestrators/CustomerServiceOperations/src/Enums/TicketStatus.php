<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Enums;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case PENDING_CUSTOMER = 'pending_customer';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}
