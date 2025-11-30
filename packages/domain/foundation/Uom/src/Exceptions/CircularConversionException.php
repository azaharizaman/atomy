<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when a circular conversion path is detected.
 *
 * This prevents infinite loops in conversion graph traversal.
 *
 * Requirements: FR-UOM-A03, BUS-UOM-201
 */
class CircularConversionException extends Exception
{
    /**
     * Create exception for circular path detection.
     *
     * @param array<string> $path The circular path detected
     * @return self
     */
    public static function forPath(array $path): self
    {
        $pathString = implode(' → ', $path);
        return new self(
            "Circular conversion path detected: {$pathString}. " .
            "Conversion rules must form a directed acyclic graph."
        );
    }
}
