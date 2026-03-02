<?php

declare(strict_types=1);

namespace Nexus\Blockchain\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
}
