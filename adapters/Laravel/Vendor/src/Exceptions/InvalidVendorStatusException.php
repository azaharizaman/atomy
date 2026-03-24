<?php
declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Exceptions;

use RuntimeException;

final class InvalidVendorStatusException extends RuntimeException
{
    public function __construct(
        public readonly string $invalidStatus
    ) {
        parent::__construct("Invalid vendor status: {$invalidStatus}");
    }
}