<?php

declare(strict_types=1);

namespace Nexus\Uom\Services;

use Nexus\Uom\Contracts\DimensionInterface;
use Nexus\Uom\Contracts\UnitInterface;
use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\Exceptions\IncompatibleUnitException;
use Nexus\Uom\Exceptions\InvalidConversionRatioException;
use Nexus\Uom\Exceptions\InvalidOffsetConversionException;
use Nexus\Uom\Exceptions\SystemUnitProtectedException;
use Nexus\Uom\ValueObjects\Quantity;

/**
 * Validation service for unit of measurement operations.
 *
 * Validates dimensions, ratios, offset usage, and business rules.
 *
 * Requirements: FR-UOM-103, BUS-UOM-102, BUS-UOM-104, BUS-UOM-106,
 *              BUS-UOM-203, BUS-UOM-301, BUS-UOM-302, SEC-UOM-104
 */
class UomValidationService
{
    /**
     * Create a new validation service.
     *
     * @param UomRepositoryInterface $repository Repository for units
     */
    public function __construct(
        private readonly UomRepositoryInterface $repository
    ) {
    }

    /**
     * Check if two quantities are convertible (same dimension).
     *
     * @param Quantity $qty1 First quantity
     * @param Quantity $qty2 Second quantity
     * @return bool True if convertible, false otherwise
     */
    public function areConvertible(Quantity $qty1, Quantity $qty2): bool
    {
        $unit1 = $this->repository->findUnitByCode($qty1->getUnitCode());
        $unit2 = $this->repository->findUnitByCode($qty2->getUnitCode());

        if ($unit1 === null || $unit2 === null) {
            return false;
        }

        return $unit1->getDimension() === $unit2->getDimension();
    }

    /**
     * Validate that two units belong to the same dimension.
     *
     * @param UnitInterface $unit1 First unit
     * @param UnitInterface $unit2 Second unit
     * @return void
     * @throws IncompatibleUnitException If dimensions don't match
     */
    public function validateSameDimension(UnitInterface $unit1, UnitInterface $unit2): void
    {
        if ($unit1->getDimension() !== $unit2->getDimension()) {
            throw IncompatibleUnitException::forDimensions(
                $unit1->getDimension(),
                $unit2->getDimension()
            );
        }
    }

    /**
     * Validate that a conversion ratio is valid.
     *
     * Ratio must be positive, non-zero, finite, and not NaN.
     *
     * @param float $ratio The ratio to validate
     * @return void
     * @throws InvalidConversionRatioException If ratio is invalid
     */
    public function validateRatio(float $ratio): void
    {
        if ($ratio === 0.0) {
            throw InvalidConversionRatioException::forRatio($ratio);
        }

        if ($ratio < 0) {
            throw InvalidConversionRatioException::forRatio($ratio);
        }

        if (is_infinite($ratio)) {
            throw InvalidConversionRatioException::forRatio($ratio);
        }

        if (is_nan($ratio)) {
            throw InvalidConversionRatioException::forRatio($ratio);
        }
    }

    /**
     * Validate that a ratio is positive (for packaging relationships).
     *
     * @param float $ratio The ratio to validate
     * @return void
     * @throws InvalidConversionRatioException If ratio is not positive
     */
    public function validatePositiveRatio(float $ratio): void
    {
        if ($ratio <= 0) {
            throw InvalidConversionRatioException::forRatio(
                $ratio,
                "Packaging ratio must be positive."
            );
        }
    }

    /**
     * Validate that offset conversions are only used for temperature dimension.
     *
     * @param DimensionInterface $dimension The dimension to check
     * @param float $offset The offset value
     * @return void
     * @throws InvalidOffsetConversionException If offset not allowed
     */
    public function validateOffsetAllowed(DimensionInterface $dimension, float $offset): void
    {
        if ($offset !== 0.0 && !$dimension->allowsOffset()) {
            throw InvalidOffsetConversionException::forDimension($dimension->getCode());
        }
    }

    /**
     * Validate that base unit for a dimension is not being changed.
     *
     * Once units are defined, the base unit cannot be changed to prevent
     * broken conversions.
     *
     * @param string $dimensionCode Dimension code
     * @param string $currentBaseUnit Current base unit code
     * @param string $newBaseUnit Proposed new base unit code
     * @return void
     * @throws \InvalidArgumentException If attempting to change base unit
     */
    public function validateBaseUnitImmutable(
        string $dimensionCode,
        string $currentBaseUnit,
        string $newBaseUnit
    ): void {
        if ($currentBaseUnit !== $newBaseUnit) {
            throw new \InvalidArgumentException(
                "Cannot change base unit for dimension '{$dimensionCode}' from '{$currentBaseUnit}' " .
                "to '{$newBaseUnit}'. Base units are immutable once defined."
            );
        }
    }

    /**
     * Validate that a unit is not a system unit (before modification).
     *
     * @param UnitInterface $unit The unit to check
     * @param string $operation The operation being attempted
     * @return void
     * @throws SystemUnitProtectedException If unit is system-defined
     */
    public function validateNotSystemUnit(UnitInterface $unit, string $operation = 'modify'): void
    {
        if ($unit->isSystemUnit()) {
            throw SystemUnitProtectedException::forUnit($unit->getCode(), $operation);
        }
    }

    /**
     * Validate that packaging relationships form a directed acyclic graph.
     *
     * This prevents circular packaging definitions like:
     * Case → Box → Pallet → Case (circular!)
     *
     * @param array<string> $path The packaging path to validate
     * @return void
     * @throws \InvalidArgumentException If circular relationship detected
     */
    public function validatePackagingDAG(array $path): void
    {
        $seen = [];
        foreach ($path as $unitCode) {
            if (isset($seen[$unitCode])) {
                throw new \InvalidArgumentException(
                    "Circular packaging relationship detected: " . implode(' → ', $path) .
                    ". Packaging must form a directed acyclic graph."
                );
            }
            $seen[$unitCode] = true;
        }
    }

    /**
     * Validate that a unit code is unique before saving.
     *
     * @param string $unitCode The unit code to check
     * @return void
     * @throws \Nexus\Uom\Exceptions\DuplicateUnitCodeException If code already exists
     */
    public function validateUniqueUnitCode(string $unitCode): void
    {
        if (!$this->repository->ensureUniqueCode($unitCode)) {
            throw new \Nexus\Uom\Exceptions\DuplicateUnitCodeException(
                "Unit code '{$unitCode}' already exists."
            );
        }
    }
}
