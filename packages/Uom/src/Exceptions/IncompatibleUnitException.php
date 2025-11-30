<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to perform operations on incompatible units.
 *
 * This occurs when trying to convert or perform arithmetic between units
 * that belong to different dimensions (e.g., adding kg to meters).
 *
 * Requirements: FR-UOM-A03, BUS-UOM-103, REL-UOM-103
 */
class IncompatibleUnitException extends Exception
{
    /**
     * Create exception for incompatible units.
     *
     * @param string $fromUnit Source unit code
     * @param string $toUnit Target unit code
     * @param string $operation Optional operation being performed
     * @return self
     */
    public static function forUnits(string $fromUnit, string $toUnit, string $operation = 'conversion'): self
    {
        return new self(
            "Cannot perform {$operation} between incompatible units: '{$fromUnit}' and '{$toUnit}'. " .
            "Units must belong to the same dimension."
        );
    }

    /**
     * Create exception for incompatible dimensions.
     *
     * @param string $dimension1 First dimension code
     * @param string $dimension2 Second dimension code
     * @return self
     */
    public static function forDimensions(string $dimension1, string $dimension2): self
    {
        return new self(
            "Cannot perform operation between units of different dimensions: " .
            "'{$dimension1}' and '{$dimension2}'."
        );
    }
}
