<?php

declare(strict_types=1);

namespace Nexus\Uom\Exceptions;

use Exception;

/**
 * Exception thrown when a conversion ratio is invalid.
 *
 * This occurs when a conversion ratio is zero, negative, infinite, or NaN.
 *
 * Requirements: FR-UOM-A03, BUS-UOM-104, REL-UOM-102
 */
class InvalidConversionRatioException extends Exception
{
    /**
     * Create exception for invalid ratio value.
     *
     * @param float $ratio The invalid ratio value
     * @param string $reason Optional reason for invalidity
     * @return self
     */
    public static function forRatio(float $ratio, string $reason = ''): self
    {
        $message = "Invalid conversion ratio: {$ratio}.";
        
        if ($ratio === 0.0) {
            $message .= " Ratio cannot be zero (division by zero).";
        } elseif ($ratio < 0) {
            $message .= " Ratio must be positive.";
        } elseif (is_infinite($ratio)) {
            $message .= " Ratio cannot be infinite.";
        } elseif (is_nan($ratio)) {
            $message .= " Ratio cannot be NaN.";
        }
        
        if ($reason !== '') {
            $message .= " {$reason}";
        }
        
        return new self($message);
    }

    /**
     * Create exception for division by zero in conversion.
     *
     * @param string $fromUnit Source unit code
     * @param string $toUnit Target unit code
     * @return self
     */
    public static function divisionByZero(string $fromUnit, string $toUnit): self
    {
        return new self(
            "Division by zero detected in conversion from '{$fromUnit}' to '{$toUnit}'. " .
            "Conversion ratio cannot be zero."
        );
    }
}
