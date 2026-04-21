<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Exceptions;

use DomainException;

final class VendorNotFoundException extends DomainException
{
    public function __construct(string $tenantId, string $vendorId)
    {
        parent::__construct(sprintf(
            'Vendor %s was not found for tenant %s.',
            $vendorId,
            $tenantId,
        ));
    }
}
