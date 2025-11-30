<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when a dimension is not found in the repository.
 *
 * Requirements: FR-UOM-A03
 */
class DimensionNotFoundException extends Exception
{
    /**
     * Create exception for missing dimension code.
     *
     * @param string $dimensionCode The dimension code that was not found
     * @return self
     */
    public static function forCode(string $dimensionCode): self
    {
        return new self("Dimension with code '{$dimensionCode}' not found in the system.");
    }
}
