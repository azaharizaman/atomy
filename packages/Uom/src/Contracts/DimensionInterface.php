<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * Interface for a Dimension (category of units).
 *
 * Represents a measurement dimension that groups related units.
 *
 * Requirements: FR-UOM-201, FR-UOM-204, ARC-UOM-0027
 */
interface DimensionInterface
{
    /**
     * Get the unique dimension code.
     *
     * @return string The dimension code (e.g., 'mass', 'length', 'temperature')
     */
    public function getCode(): string;

    /**
     * Get the human-readable dimension name.
     *
     * @return string The dimension name (e.g., 'Mass', 'Length')
     */
    public function getName(): string;

    /**
     * Get the base unit code for this dimension.
     *
     * All conversions within this dimension must route through this base unit.
     *
     * @return string The base unit code (e.g., 'kg' for mass, 'm' for length)
     */
    public function getBaseUnit(): string;

    /**
     * Get optional description of the dimension.
     *
     * @return string|null Description text or null
     */
    public function getDescription(): ?string;

    /**
     * Check if this dimension allows offset conversions.
     *
     * Only temperature dimension typically allows offset conversions.
     *
     * @return bool True if offset conversions allowed, false otherwise
     */
    public function allowsOffset(): bool;
}
