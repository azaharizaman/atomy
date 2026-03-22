<?php

declare(strict_types=1);

namespace Nexus\Outbox\Enums;

enum OutboxRecordStatus
{
    case Pending;
    case Sending;
    case Sent;
    case Failed;
}
