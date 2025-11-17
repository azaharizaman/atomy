<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when no conversion path exists between two units.
 *
 * This occurs when units are in disconnected dimensions or no conversion
 * rules have been defined to link them.
 *
 * Requirements: FR-UOM-A03, REL-UOM-104
 */
class ConversionPathNotFoundException extends Exception
{
    /**
     * Create exception for missing conversion path.
     *
     * @param string $fromUnit Source unit code
     * @param string $toUnit Target unit code
     * @return self
     */
    public static function between(string $fromUnit, string $toUnit): self
    {
        return new self(
            "No conversion path found from '{$fromUnit}' to '{$toUnit}'. " .
            "Units may be in different dimensions or conversion rules are missing."
        );
    }
}
