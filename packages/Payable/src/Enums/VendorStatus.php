<?php

declare(strict_types=1);

namespace Nexus\Payable\Enums;

/**
 * Vendor status enum.
 */
enum VendorStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';
    case PENDING_APPROVAL = 'pending_approval';
}
