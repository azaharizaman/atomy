<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * Interface for Unit of Measurement repository operations.
 *
 * Defines all persistence operations for units, dimensions, conversions,
 * and unit systems without coupling to any specific storage implementation.
 *
 * Requirements: FR-UOM-A02, ARC-UOM-0027, FR-UOM-201, FR-UOM-203
 */
interface UomRepositoryInterface
{
    /**
     * Find a unit by its unique code.
     *
     * @param string $code The unit code (e.g., 'kg', 'm', 'lb')
     * @return UnitInterface|null The unit if found, null otherwise
     */
    public function findUnitByCode(string $code): ?UnitInterface;

    /**
     * Find a dimension by its unique code.
     *
     * @param string $code The dimension code (e.g., 'mass', 'length')
     * @return DimensionInterface|null The dimension if found, null otherwise
     */
    public function findDimensionByCode(string $code): ?DimensionInterface;

    /**
     * Get all units belonging to a specific dimension.
     *
     * @param string $dimensionCode The dimension code
     * @return UnitInterface[] Array of units in the dimension
     */
    public function getUnitsByDimension(string $dimensionCode): array;

    /**
     * Get all units belonging to a specific unit system.
     *
     * @param string $systemCode The unit system code (e.g., 'metric', 'imperial')
     * @return UnitInterface[] Array of units in the system
     */
    public function getUnitsBySystem(string $systemCode): array;

    /**
     * Find a direct conversion rule between two units.
     *
     * @param string $fromUnitCode Source unit code
     * @param string $toUnitCode Target unit code
     * @return ConversionRuleInterface|null The conversion rule if exists, null otherwise
     */
    public function findConversion(string $fromUnitCode, string $toUnitCode): ?ConversionRuleInterface;

    /**
     * Get all conversion rules where the given unit is the source.
     *
     * @param string $fromUnitCode Source unit code
     * @return ConversionRuleInterface[] Array of conversion rules
     */
    public function getConversionsFrom(string $fromUnitCode): array;

    /**
     * Get all conversion rules for units within a dimension.
     *
     * @param string $dimensionCode The dimension code
     * @return ConversionRuleInterface[] Array of conversion rules
     */
    public function getConversionsByDimension(string $dimensionCode): array;

    /**
     * Save a new unit definition.
     *
     * @param UnitInterface $unit The unit to save
     * @return UnitInterface The saved unit with any generated IDs
     * @throws \Nexus\Uom\Exceptions\DuplicateUnitCodeException If code already exists
     */
    public function saveUnit(UnitInterface $unit): UnitInterface;

    /**
     * Save a new dimension definition.
     *
     * @param DimensionInterface $dimension The dimension to save
     * @return DimensionInterface The saved dimension
     * @throws \Nexus\Uom\Exceptions\DuplicateDimensionCodeException If code already exists
     */
    public function saveDimension(DimensionInterface $dimension): DimensionInterface;

    /**
     * Save a new conversion rule.
     *
     * @param ConversionRuleInterface $rule The conversion rule to save
     * @return ConversionRuleInterface The saved rule
     * @throws \Nexus\Uom\Exceptions\InvalidConversionRatioException If ratio is invalid
     */
    public function saveConversion(ConversionRuleInterface $rule): ConversionRuleInterface;

    /**
     * Check if a unit code is unique in the system.
     *
     * @param string $code The unit code to check
     * @return bool True if unique, false if already exists
     */
    public function ensureUniqueCode(string $code): bool;

    /**
     * Get all defined dimensions in the system.
     *
     * @return DimensionInterface[] Array of all dimensions
     */
    public function getAllDimensions(): array;

    /**
     * Get all defined unit systems.
     *
     * @return UnitSystemInterface[] Array of all unit systems
     */
    public function getAllUnitSystems(): array;
}
