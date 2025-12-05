<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that can be converted between units.
 *
 * Provides unit-to-unit conversion capabilities for measurement value objects.
 * This is distinct from CurrencyConvertible which handles currency-specific
 * conversions with exchange rates.
 *
 * **When to Use:**
 * - Weight measurements (kg ↔ lb, g ↔ oz)
 * - Length measurements (m ↔ ft, cm ↔ in)
 * - Volume measurements (L ↔ gal, ml ↔ fl oz)
 * - Temperature (°C ↔ °F ↔ K)
 * - Any quantity with multiple unit representations
 *
 * **Expected Behavior:**
 * - `convertTo()` returns a NEW instance in the target unit (immutability)
 * - `canConvertTo()` validates if conversion is possible BEFORE attempting
 * - Conversion factors should be managed by Nexus\Uom package
 * - Precision loss should be minimized during conversion
 *
 * **Integration with Nexus\Uom:**
 * This interface works alongside the Uom package which provides:
 * - Unit definitions and categories
 * - Conversion factors between units
 * - Unit validation and normalization
 *
 * @example Example implementation for Weight:
 * ```php
 * final readonly class Weight implements Convertible
 * {
 *     public function __construct(
 *         private float $value,
 *         private string $unit,
 *         private UomConverterInterface $converter
 *     ) {}
 *
 *     public function convertTo(string $toUnit): static
 *     {
 *         if (!$this->canConvertTo($toUnit)) {
 *             throw new \InvalidArgumentException("Cannot convert to {$toUnit}");
 *         }
 *
 *         $converted = $this->converter->convert(
 *             $this->value,
 *             $this->unit,
 *             $toUnit
 *         );
 *
 *         return new self($converted, $toUnit, $this->converter);
 *     }
 *
 *     public function canConvertTo(string $toUnit): bool
 *     {
 *         return $this->converter->canConvert($this->unit, $toUnit);
 *     }
 * }
 *
 * // Usage
 * $weight = new Weight(100, 'kg', $uomConverter);
 * $inPounds = $weight->convertTo('lb'); // ~220.46 lb
 * ```
 */
interface Convertible
{
    /**
     * Convert to another unit.
     * 
     * @param string $toUnit Target unit code
     * @return static New instance in the target unit
     */
    public function convertTo(string $toUnit): static;

    /**
     * Check if conversion to target unit is supported.
     */
    public function canConvertTo(string $toUnit): bool;
}
