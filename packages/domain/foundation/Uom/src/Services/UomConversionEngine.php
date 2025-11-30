<?php

declare(strict_types=1);

namespace Nexus\Uom\Services;

use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\Exceptions\CircularConversionException;
use Nexus\Uom\Exceptions\ConversionPathNotFoundException;
use Nexus\Uom\Exceptions\IncompatibleUnitException;
use Nexus\Uom\Exceptions\InvalidConversionRatioException;
use Nexus\Uom\Exceptions\UnitNotFoundException;

/**
 * Core conversion engine for unit of measurement conversions.
 *
 * Implements graph-based pathfinding for multi-hop conversions,
 * direct ratio conversions, and offset conversions for temperature.
 *
 * Requirements: FR-UOM-102, FR-UOM-202, FR-UOM-205, FR-UOM-301, FR-UOM-302,
 *              BUS-UOM-201, BUS-UOM-202, PER-UOM-101, PER-UOM-102, REL-UOM-101
 */
class UomConversionEngine
{
    /** @var array<string, float> Cache for conversion paths */
    private array $conversionCache = [];

    /**
     * Create a new conversion engine.
     *
     * @param UomRepositoryInterface $repository Repository for units and conversions
     * @param UomValidationService $validator Validation service
     */
    public function __construct(
        private readonly UomRepositoryInterface $repository,
        private readonly UomValidationService $validator
    ) {
    }

    /**
     * Convert a value from one unit to another.
     *
     * This is the main conversion method that handles direct conversions,
     * multi-hop conversions via base units, and caching.
     *
     * @param float $value The value to convert
     * @param string $fromUnitCode Source unit code
     * @param string $toUnitCode Target unit code
     * @return float The converted value
     * @throws UnitNotFoundException If either unit doesn't exist
     * @throws IncompatibleUnitException If units are from different dimensions
     * @throws ConversionPathNotFoundException If no conversion path exists
     * @throws InvalidConversionRatioException If conversion ratio is invalid
     */
    public function convert(float $value, string $fromUnitCode, string $toUnitCode): float
    {
        // Same unit, no conversion needed
        if ($fromUnitCode === $toUnitCode) {
            return $value;
        }

        // Check cache
        $cacheKey = "{$fromUnitCode}:{$toUnitCode}";
        if (isset($this->conversionCache[$cacheKey])) {
            return $value * $this->conversionCache[$cacheKey];
        }

        // Validate units exist and are compatible
        $fromUnit = $this->repository->findUnitByCode($fromUnitCode);
        $toUnit = $this->repository->findUnitByCode($toUnitCode);

        if ($fromUnit === null) {
            throw UnitNotFoundException::forCode($fromUnitCode);
        }
        if ($toUnit === null) {
            throw UnitNotFoundException::forCode($toUnitCode);
        }

        $this->validator->validateSameDimension($fromUnit, $toUnit);

        // Try direct conversion
        $directRule = $this->repository->findConversion($fromUnitCode, $toUnitCode);
        if ($directRule !== null) {
            $this->validator->validateRatio($directRule->getRatio());
            $converted = ($value * $directRule->getRatio()) + $directRule->getOffset();
            
            // Cache the ratio (without offset for simplicity)
            if (!$directRule->hasOffset()) {
                $this->conversionCache[$cacheKey] = $directRule->getRatio();
            }
            
            return $converted;
        }

        // Multi-hop conversion via base unit
        return $this->convertViaBaseUnit($value, $fromUnitCode, $toUnitCode, $fromUnit->getDimension());
    }

    /**
     * Convert via the dimension's base unit as intermediary.
     *
     * @param float $value Value to convert
     * @param string $fromUnitCode Source unit
     * @param string $toUnitCode Target unit
     * @param string $dimensionCode Dimension code
     * @return float Converted value
     * @throws ConversionPathNotFoundException
     */
    public function convertViaBaseUnit(
        float $value,
        string $fromUnitCode,
        string $toUnitCode,
        string $dimensionCode
    ): float {
        $dimension = $this->repository->findDimensionByCode($dimensionCode);
        if ($dimension === null) {
            throw ConversionPathNotFoundException::between($fromUnitCode, $toUnitCode);
        }

        $baseUnit = $dimension->getBaseUnit();

        // Convert from source to base
        $valueInBase = $fromUnitCode === $baseUnit
            ? $value
            : $this->convert($value, $fromUnitCode, $baseUnit);

        // Convert from base to target
        return $baseUnit === $toUnitCode
            ? $valueInBase
            : $this->convert($valueInBase, $baseUnit, $toUnitCode);
    }

    /**
     * Convert with explicit offset support (for temperature).
     *
     * @param float $value Value to convert
     * @param string $fromUnitCode Source unit
     * @param string $toUnitCode Target unit
     * @param float $ratio Conversion ratio
     * @param float $offset Conversion offset
     * @return float Converted value
     */
    public function convertWithOffset(
        float $value,
        string $fromUnitCode,
        string $toUnitCode,
        float $ratio,
        float $offset
    ): float {
        $this->validator->validateRatio($ratio);
        return ($value * $ratio) + $offset;
    }

    /**
     * Find the conversion path between two units using graph traversal.
     *
     * This is used for discovering conversion paths in complex scenarios.
     *
     * @param string $fromUnitCode Source unit
     * @param string $toUnitCode Target unit
     * @return array<string> Array of unit codes representing the path
     * @throws ConversionPathNotFoundException
     * @throws CircularConversionException
     */
    public function findConversionPath(string $fromUnitCode, string $toUnitCode): array
    {
        if ($fromUnitCode === $toUnitCode) {
            return [$fromUnitCode];
        }

        $visited = [];
        $queue = [[$fromUnitCode]];

        while (!empty($queue)) {
            $path = array_shift($queue);
            $current = end($path);

            if ($current === $toUnitCode) {
                return $path;
            }

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            // Get all conversions from current unit
            $conversions = $this->repository->getConversionsFrom($current);
            foreach ($conversions as $rule) {
                $next = $rule->getToUnit();
                
                // Check for circular path
                if (in_array($next, $path, true)) {
                    throw CircularConversionException::forPath([...$path, $next]);
                }

                $newPath = [...$path, $next];
                $queue[] = $newPath;
            }
        }

        throw ConversionPathNotFoundException::between($fromUnitCode, $toUnitCode);
    }

    /**
     * Convert packaging units to base unit equivalent.
     *
     * Example: 5 Cases (where 1 Case = 100 Eaches) → 500 Eaches
     *
     * @param float $value Quantity in packaging unit
     * @param string $packagingUnitCode Packaging unit code
     * @param string $baseUnitCode Base unit code (e.g., 'each')
     * @return float Quantity in base units
     */
    public function packagingToBase(float $value, string $packagingUnitCode, string $baseUnitCode): float
    {
        return $this->convert($value, $packagingUnitCode, $baseUnitCode);
    }

    /**
     * Convert between packaging levels.
     *
     * Example: 2 Pallets → Cases (where 1 Pallet = 10 Cases)
     *
     * @param float $value Quantity in source packaging
     * @param string $fromPackaging Source packaging unit
     * @param string $toPackaging Target packaging unit
     * @return float Quantity in target packaging
     */
    public function convertPackaging(float $value, string $fromPackaging, string $toPackaging): float
    {
        return $this->convert($value, $fromPackaging, $toPackaging);
    }

    /**
     * Detect if a circular conversion path exists.
     *
     * @param string $unitCode Starting unit code
     * @return bool True if circular path detected, false otherwise
     */
    public function detectCircularPath(string $unitCode): bool
    {
        try {
            $this->findConversionPath($unitCode, $unitCode);
            return true; // If we can get back to start, it's circular
        } catch (ConversionPathNotFoundException) {
            return false;
        } catch (CircularConversionException) {
            return true;
        }
    }

    /**
     * Clear the conversion cache.
     *
     * Call this when conversion rules are modified.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->conversionCache = [];
    }
}
