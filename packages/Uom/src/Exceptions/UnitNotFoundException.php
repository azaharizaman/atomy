<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when a unit is not found in the repository.
 *
 * Requirements: FR-UOM-A03
 */
class UnitNotFoundException extends Exception
{
    /**
     * Create exception for missing unit code.
     *
     * @param string $unitCode The unit code that was not found
     * @return self
     */
    public static function forCode(string $unitCode): self
    {
        return new self("Unit with code '{$unitCode}' not found in the system.");
    }
}
