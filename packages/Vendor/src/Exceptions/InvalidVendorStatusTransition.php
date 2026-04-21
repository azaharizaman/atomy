<?php

declare(strict_types=1);

namespace Nexus\Vendor\Exceptions;

use DomainException;
use Nexus\Vendor\Enums\VendorStatus;

final class InvalidVendorStatusTransition extends DomainException
{
    public function __construct(VendorStatus $from, VendorStatus $to)
    {
        parent::__construct(sprintf(
            'Cannot transition vendor status from %s to %s.',
            $from->name,
            $to->name,
        ));
    }
}
