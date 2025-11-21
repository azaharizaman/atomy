<?php

declare(strict_types=1);

namespace Nexus\Inventory\Enums;

/**
 * Stock transfer workflow states
 */
enum TransferStatus: string
{
    case PENDING = 'pending';
    case IN_TRANSIT = 'in_transit';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
