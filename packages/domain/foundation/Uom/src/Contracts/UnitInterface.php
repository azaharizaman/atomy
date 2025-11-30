<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * Interface for a Unit of Measurement.
 *
 * Represents a single unit with its properties and relationships.
 *
 * Requirements: ARC-UOM-0027, BUS-UOM-105, BUS-UOM-204
 */
interface UnitInterface
{
    /**
     * Get the unique unit code.
     *
     * @return string The unit code (e.g., 'kg', 'm', 'lb')
     */
    public function getCode(): string;

    /**
     * Get the human-readable unit name.
     *
     * @return string The unit name (e.g., 'Kilogram', 'Meter')
     */
    public function getName(): string;

    /**
     * Get the unit symbol for display.
     *
     * @return string The unit symbol (e.g., 'kg', 'm')
     */
    public function getSymbol(): string;

    /**
     * Get the dimension this unit belongs to.
     *
     * @return string The dimension code (e.g., 'mass', 'length')
     */
    public function getDimension(): string;

    /**
     * Get the unit system this unit belongs to.
     *
     * @return string|null The system code (e.g., 'metric', 'imperial')
     */
    public function getSystem(): ?string;

    /**
     * Check if this is a base unit for its dimension.
     *
     * @return bool True if base unit, false otherwise
     */
    public function isBaseUnit(): bool;

    /**
     * Check if this is a system-defined unit (cannot be modified/deleted).
     *
     * @return bool True if system unit, false if custom
     */
    public function isSystemUnit(): bool;
}
