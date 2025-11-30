<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when a duplicate dimension code is detected.
 *
 * Requirements: FR-UOM-A03
 */
class DuplicateDimensionCodeException extends Exception
{
    /**
     * Create exception for duplicate dimension code.
     *
     * @param string $dimensionCode The duplicate dimension code
     * @return self
     */
    public static function forCode(string $dimensionCode): self
    {
        return new self(
            "Dimension code '{$dimensionCode}' already exists. Dimension codes must be unique."
        );
    }
}
