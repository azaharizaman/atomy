<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * Interface for a Unit System (e.g., Metric, Imperial).
 *
 * Represents a collection of units that belong to the same measurement system.
 *
 * Requirements: FR-UOM-203
 */
interface UnitSystemInterface
{
    /**
     * Get the unique system code.
     *
     * @return string The system code (e.g., 'metric', 'imperial', 'us')
     */
    public function getCode(): string;

    /**
     * Get the human-readable system name.
     *
     * @return string The system name (e.g., 'Metric System', 'Imperial System')
     */
    public function getName(): string;

    /**
     * Get optional description of the system.
     *
     * @return string|null Description text or null
     */
    public function getDescription(): ?string;

    /**
     * Check if this is a system-defined unit system.
     *
     * @return bool True if system-defined, false if custom
     */
    public function isSystemDefined(): bool;
}
