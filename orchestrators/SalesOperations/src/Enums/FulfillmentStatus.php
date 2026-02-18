<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Enums;

enum FulfillmentStatus: string
{
    case PENDING = 'pending';
    case RESERVED = 'reserved';
    case PICKING = 'picking';
    case PACKED = 'packed';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function isComplete(): bool
    {
        return in_array($this, [self::DELIVERED]);
    }

    public function isInProgress(): bool
    {
        return in_array($this, [self::RESERVED, self::PICKING, self::PACKED, self::SHIPPED]);
    }
}
