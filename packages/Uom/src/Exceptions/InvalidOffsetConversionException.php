<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to use offset conversions on incompatible dimensions.
 *
 * Only temperature dimension should allow offset conversions.
 *
 * Requirements: FR-UOM-A03, BUS-UOM-203
 */
class InvalidOffsetConversionException extends Exception
{
    /**
     * Create exception for invalid offset usage.
     *
     * @param string $dimensionCode The dimension where offset was attempted
     * @return self
     */
    public static function forDimension(string $dimensionCode): self
    {
        return new self(
            "Offset conversions are not allowed for dimension '{$dimensionCode}'. " .
            "Only temperature dimension supports offset conversions."
        );
    }
}
