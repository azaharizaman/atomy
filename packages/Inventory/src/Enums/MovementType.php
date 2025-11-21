<?php

declare(strict_types=1);

namespace Nexus\Inventory\Enums;

/**
 * Stock movement types
 */
enum MovementType: string
{
    case RECEIPT = 'receipt';
    case ISSUE = 'issue';
    case ADJUSTMENT = 'adjustment';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';
    case RESERVATION = 'reservation';
    case RESERVATION_RELEASE = 'reservation_release';
}
