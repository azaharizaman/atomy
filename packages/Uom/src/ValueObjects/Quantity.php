<?php

declare(strict_types=1);

namespace Nexus\Uom\ValueObjects;

use JsonSerializable;
use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Uom\Services\UomValidationService;

/**
 * Immutable Value Object representing a quantity with value and unit.
 *
 * This is the primary API entry point for working with measurements.
 * All instances are immutable - operations return new instances.
 *
 * Requirements: FR-UOM-101, FR-UOM-104, FR-UOM-105, FR-UOM-303, BUS-UOM-101
 */
final readonly class Quantity implements JsonSerializable
{
    /**
     * Create a new immutable Quantity.
     *
     * @param float $value The numeric value
     * @param string $unitCode The unit code (e.g., 'kg', 'm', 'lb')
     */
    public function __construct(
        public float $value,
        public string $unitCode
    ) {
    }

    /**
     * Get the numeric value.
     *
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Get the unit code.
     *
     * @return string
     */
    public function getUnitCode(): string
    {
        return $this->unitCode;
    }

    /**
     * Convert this quantity to a different unit.
     *
     * @param string $toUnitCode Target unit code
     * @param UomConversionEngine $engine Conversion engine
     * @return self New quantity in target unit
     * @throws \Nexus\Uom\Exceptions\IncompatibleUnitException
     * @throws \Nexus\Uom\Exceptions\ConversionPathNotFoundException
     */
    public function convertTo(string $toUnitCode, UomConversionEngine $engine): self
    {
        if ($this->unitCode === $toUnitCode) {
            return $this;
        }

        $convertedValue = $engine->convert($this->value, $this->unitCode, $toUnitCode);
        return new self($convertedValue, $toUnitCode);
    }

    /**
     * Add another quantity to this one (with automatic conversion).
     *
     * @param self $other The quantity to add
     * @param UomConversionEngine $engine Conversion engine
     * @return self New quantity with sum in this unit
     * @throws \Nexus\Uom\Exceptions\IncompatibleUnitException
     */
    public function add(self $other, UomConversionEngine $engine): self
    {
        $converted = $other->convertTo($this->unitCode, $engine);
        return new self($this->value + $converted->value, $this->unitCode);
    }

    /**
     * Subtract another quantity from this one (with automatic conversion).
     *
     * @param self $other The quantity to subtract
     * @param UomConversionEngine $engine Conversion engine
     * @return self New quantity with difference in this unit
     * @throws \Nexus\Uom\Exceptions\IncompatibleUnitException
     */
    public function subtract(self $other, UomConversionEngine $engine): self
    {
        $converted = $other->convertTo($this->unitCode, $engine);
        return new self($this->value - $converted->value, $this->unitCode);
    }

    /**
     * Multiply this quantity by a scalar value.
     *
     * @param float $scalar The multiplier
     * @return self New quantity with product
     */
    public function multiply(float $scalar): self
    {
        return new self($this->value * $scalar, $this->unitCode);
    }

    /**
     * Divide this quantity by a scalar value.
     *
     * @param float $scalar The divisor
     * @return self New quantity with quotient
     * @throws \DivisionByZeroError If scalar is zero
     */
    public function divide(float $scalar): self
    {
        if ($scalar === 0.0) {
            throw new \DivisionByZeroError("Cannot divide quantity by zero");
        }

        return new self($this->value / $scalar, $this->unitCode);
    }

    /**
     * Format the quantity for display with locale-specific formatting.
     *
     * @param string $locale Locale code (e.g., 'en_US', 'de_DE')
     * @param int $decimals Number of decimal places (default 2)
     * @return string Formatted quantity with unit
     */
    public function format(string $locale = 'en_US', int $decimals = 2): string
    {
        $decimalSep = ($locale === 'de_DE' || $locale === 'fr_FR') ? ',' : '.';
        $thousandsSep = ($locale === 'de_DE' || $locale === 'fr_FR') ? '.' : ',';

        $formatted = number_format($this->value, $decimals, $decimalSep, $thousandsSep);
        return "{$formatted} {$this->unitCode}";
    }

    /**
     * Format with locale-aware formatting using NumberFormatter.
     *
     * @param \Nexus\Localization\ValueObjects\Locale $locale Target locale
     * @param \Nexus\Localization\Services\NumberFormatter $formatter Number formatter service
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Localized quantity with unit (e.g., "1.234,56 kg" for de_DE)
     */
    public function formatLocalized(
        \Nexus\Localization\ValueObjects\Locale $locale,
        \Nexus\Localization\Services\NumberFormatter $formatter,
        int $decimals = 2
    ): string {
        $formattedValue = $formatter->format($this->value, $locale, null);
        return "{$formattedValue} {$this->unitCode}";
    }

    /**
     * Compare if this quantity equals another (after conversion).
     *
     * @param self $other The quantity to compare
     * @param UomConversionEngine $engine Conversion engine
     * @param float $epsilon Precision tolerance (default 0.0001)
     * @return bool True if equal within epsilon, false otherwise
     */
    public function equals(self $other, UomConversionEngine $engine, float $epsilon = 0.0001): bool
    {
        $converted = $other->convertTo($this->unitCode, $engine);
        return abs($this->value - $converted->value) < $epsilon;
    }

    /**
     * Compare if this quantity is greater than another.
     *
     * @param self $other The quantity to compare
     * @param UomConversionEngine $engine Conversion engine
     * @return bool True if this is greater, false otherwise
     */
    public function greaterThan(self $other, UomConversionEngine $engine): bool
    {
        $converted = $other->convertTo($this->unitCode, $engine);
        return $this->value > $converted->value;
    }

    /**
     * Compare if this quantity is less than another.
     *
     * @param self $other The quantity to compare
     * @param UomConversionEngine $engine Conversion engine
     * @return bool True if this is less, false otherwise
     */
    public function lessThan(self $other, UomConversionEngine $engine): bool
    {
        $converted = $other->convertTo($this->unitCode, $engine);
        return $this->value < $converted->value;
    }

    /**
     * Convert to array representation.
     *
     * @return array{value: float, unit: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unitCode,
        ];
    }

    /**
     * Convert to JSON representation.
     *
     * @return string JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Create from array representation.
     *
     * @param array{value: float, unit: string} $data Array with value and unit keys
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data['value'], $data['unit']);
    }

    /**
     * Create from JSON representation.
     *
     * @param string $json JSON string
     * @return self
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    /**
     * JSON serialization support.
     *
     * @return array{value: float, unit: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
