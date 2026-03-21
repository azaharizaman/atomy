<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Enums;

enum IdempotencyRecordStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';
}
