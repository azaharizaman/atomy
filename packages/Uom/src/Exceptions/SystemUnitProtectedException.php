<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to modify or delete a system-defined unit.
 *
 * System units are protected from modification to ensure data integrity.
 *
 * Requirements: FR-UOM-A03, SEC-UOM-104
 */
class SystemUnitProtectedException extends Exception
{
    /**
     * Create exception for protected system unit.
     *
     * @param string $unitCode The system unit code
     * @param string $operation The operation attempted (modify/delete)
     * @return self
     */
    public static function forUnit(string $unitCode, string $operation = 'modify'): self
    {
        return new self(
            "Cannot {$operation} system-defined unit '{$unitCode}'. " .
            "System units are protected to ensure data integrity."
        );
    }
}
