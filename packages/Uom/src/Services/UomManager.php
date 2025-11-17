<?php

declare(strict_types=1);

namespace Nexus\Uom\Services;

use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\ValueObjects\Dimension;
use Nexus\Uom\ValueObjects\Unit;
use Nexus\Uom\ValueObjects\ConversionRule;

/**
 * Manager service for Unit of Measurement operations.
 *
 * This is the main public API for the UoM package, providing high-level
 * operations for managing units, dimensions, and conversions.
 *
 * Requirements: ARC-UOM-0028, FR-UOM-A02
 */
class UomManager
{
    /**
     * Create a new UoM Manager.
     *
     * @param UomRepositoryInterface $repository Repository for persistence
     * @param UomConversionEngine $conversionEngine Conversion engine
     * @param UomValidationService $validationService Validation service
     */
    public function __construct(
        private readonly UomRepositoryInterface $repository,
        private readonly UomConversionEngine $conversionEngine,
        private readonly UomValidationService $validationService
    ) {
    }

    /**
     * Get the conversion engine.
     *
     * @return UomConversionEngine
     */
    public function getConversionEngine(): UomConversionEngine
    {
        return $this->conversionEngine;
    }

    /**
     * Get the validation service.
     *
     * @return UomValidationService
     */
    public function getValidationService(): UomValidationService
    {
        return $this->validationService;
    }

    /**
     * Create and save a new dimension.
     *
     * @param string $code Unique dimension code
     * @param string $name Human-readable name
     * @param string $baseUnit Base unit code for this dimension
     * @param bool $allowsOffset Whether offset conversions are allowed
     * @param string|null $description Optional description
     * @return Dimension The saved dimension
     */
    public function createDimension(
        string $code,
        string $name,
        string $baseUnit,
        bool $allowsOffset = false,
        ?string $description = null
    ): Dimension {
        $dimension = new Dimension($code, $name, $baseUnit, $allowsOffset, $description);
        return $this->repository->saveDimension($dimension);
    }

    /**
     * Create and save a new unit.
     *
     * @param string $code Unique unit code
     * @param string $name Human-readable name
     * @param string $symbol Display symbol
     * @param string $dimension Dimension code
     * @param string|null $system Unit system code
     * @param bool $isBaseUnit Whether this is base unit for dimension
     * @param bool $isSystemUnit Whether this is system-defined
     * @return Unit The saved unit
     */
    public function createUnit(
        string $code,
        string $name,
        string $symbol,
        string $dimension,
        ?string $system = null,
        bool $isBaseUnit = false,
        bool $isSystemUnit = false
    ): Unit {
        $this->validationService->validateUniqueUnitCode($code);
        
        $unit = new Unit($code, $name, $symbol, $dimension, $system, $isBaseUnit, $isSystemUnit);
        return $this->repository->saveUnit($unit);
    }

    /**
     * Create and save a conversion rule between two units.
     *
     * @param string $fromUnit Source unit code
     * @param string $toUnit Target unit code
     * @param float $ratio Conversion ratio
     * @param float $offset Conversion offset (default 0.0)
     * @param bool $isBidirectional Whether inverse is also valid
     * @return ConversionRule The saved conversion rule
     */
    public function createConversion(
        string $fromUnit,
        string $toUnit,
        float $ratio,
        float $offset = 0.0,
        bool $isBidirectional = true
    ): ConversionRule {
        $this->validationService->validateRatio($ratio);
        
        $rule = new ConversionRule($fromUnit, $toUnit, $ratio, $offset, $isBidirectional);
        return $this->repository->saveConversion($rule);
    }

    /**
     * Get a unit by its code.
     *
     * @param string $code Unit code
     * @return Unit|null The unit if found, null otherwise
     */
    public function getUnit(string $code): ?Unit
    {
        return $this->repository->findUnitByCode($code);
    }

    /**
     * Get a dimension by its code.
     *
     * @param string $code Dimension code
     * @return Dimension|null The dimension if found, null otherwise
     */
    public function getDimension(string $code): ?Dimension
    {
        return $this->repository->findDimensionByCode($code);
    }

    /**
     * Get all units for a specific dimension.
     *
     * @param string $dimensionCode Dimension code
     * @return array<Unit> Array of units
     */
    public function getUnitsByDimension(string $dimensionCode): array
    {
        return $this->repository->getUnitsByDimension($dimensionCode);
    }

    /**
     * Get all units for a specific system.
     *
     * @param string $systemCode System code (e.g., 'metric', 'imperial')
     * @return array<Unit> Array of units
     */
    public function getUnitsBySystem(string $systemCode): array
    {
        return $this->repository->getUnitsBySystem($systemCode);
    }

    /**
     * Get all defined dimensions.
     *
     * @return array<Dimension> Array of dimensions
     */
    public function getAllDimensions(): array
    {
        return $this->repository->getAllDimensions();
    }
}
