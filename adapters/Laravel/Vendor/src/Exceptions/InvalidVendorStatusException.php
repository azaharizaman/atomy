<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Exceptions;

use DomainException;

final class InvalidVendorStatusException extends DomainException
{
    public function __construct(string $status)
    {
        parent::__construct(sprintf('Invalid vendor status value: %s.', $status));
    }
}
