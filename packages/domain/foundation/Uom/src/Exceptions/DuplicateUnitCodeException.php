<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when a duplicate unit code is detected.
 *
 * Unit codes must be unique across the entire system.
 *
 * Requirements: FR-UOM-A03, BUS-UOM-204
 */
class DuplicateUnitCodeException extends Exception
{
    /**
     * Create exception for duplicate unit code.
     *
     * @param string $unitCode The duplicate unit code
     * @return self
     */
    public static function forCode(string $unitCode): self
    {
        return new self(
            "Unit code '{$unitCode}' already exists. Unit codes must be unique across the system."
        );
    }
}
