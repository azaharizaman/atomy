<?php
declare(strict_types=1);

namespace Nexus\Vendor\ValueObjects;

enum VendorStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
