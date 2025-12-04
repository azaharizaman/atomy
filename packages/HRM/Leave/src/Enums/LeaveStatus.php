<?php

declare(strict_types=1);

namespace Nexus\Leave\Enums;

enum LeaveStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
