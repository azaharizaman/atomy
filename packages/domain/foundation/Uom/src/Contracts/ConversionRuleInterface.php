<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * Interface for a conversion rule between two units.
 *
 * Defines how to convert from one unit to another using a ratio and optional offset.
 *
 * Requirements: FR-UOM-102, FR-UOM-205, BUS-UOM-104
 */
interface ConversionRuleInterface
{
    /**
     * Get the source unit code.
     *
     * @return string The from unit code
     */
    public function getFromUnit(): string;

    /**
     * Get the target unit code.
     *
     * @return string The to unit code
     */
    public function getToUnit(): string;

    /**
     * Get the multiplication ratio for conversion.
     *
     * Formula: toValue = (fromValue * ratio) + offset
     *
     * @return float The conversion ratio (must be positive non-zero)
     */
    public function getRatio(): float;

    /**
     * Get the offset for conversion (used for temperature).
     *
     * @return float The offset value (default 0.0)
     */
    public function getOffset(): float;

    /**
     * Check if this conversion uses an offset.
     *
     * @return bool True if offset is non-zero, false otherwise
     */
    public function hasOffset(): bool;

    /**
     * Check if this is a bidirectional conversion.
     *
     * If true, the inverse conversion is also valid.
     *
     * @return bool True if bidirectional, false if one-way
     */
    public function isBidirectional(): bool;
}
