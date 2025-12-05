<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects representing enumerated/fixed-set values.
 *
 * Provides a contract for value objects that represent a fixed set of valid values,
 * similar to PHP native enums but with additional functionality and flexibility
 * for database-backed or configurable enumerations.
 *
 * **When to Use:**
 * - Dynamic enumerations loaded from database/config
 * - Enumerated values requiring additional metadata
 * - When PHP native enums are insufficient (e.g., need localization)
 * - For backwards compatibility with systems expecting string values
 *
 * **When to Use Native PHP Enums Instead:**
 * - Static, compile-time known value sets
 * - Simple status codes without metadata
 * - When type-safety is the primary concern
 *
 * **Expected Behavior:**
 * - `values()` returns ALL valid instances (cached for performance)
 * - `getValue()` returns the underlying string code/identifier
 * - `isValid()` validates against the known value set
 * - Instances with same value SHOULD be equal
 *
 * @example Example implementation for PaymentMethod:
 * ```php
 * final readonly class PaymentMethod implements Enumable
 * {
 *     private static ?array $instances = null;
 *
 *     private function __construct(
 *         private string $value,
 *         private string $label
 *     ) {}
 *
 *     public static function values(): array
 *     {
 *         return self::$instances ??= [
 *             new self('cash', 'Cash'),
 *             new self('card', 'Credit/Debit Card'),
 *             new self('bank_transfer', 'Bank Transfer'),
 *             new self('cheque', 'Cheque'),
 *         ];
 *     }
 *
 *     public function getValue(): string { return $this->value; }
 *     public function getLabel(): string { return $this->label; }
 *
 *     public static function isValid(string $value): bool
 *     {
 *         foreach (self::values() as $method) {
 *             if ($method->getValue() === $value) return true;
 *         }
 *         return false;
 *     }
 *
 *     public static function fromValue(string $value): self
 *     {
 *         foreach (self::values() as $method) {
 *             if ($method->getValue() === $value) return $method;
 *         }
 *         throw new \InvalidArgumentException("Invalid payment method: {$value}");
 *     }
 * }
 * ```
 */
interface Enumable
{
    /**
     * Get all possible values.
     * 
     * @return array<static>
     */
    public static function values(): array;

    /**
     * Get the value/code.
     */
    public function getValue(): string;

    /**
     * Check if this value is valid.
     */
    public static function isValid(string $value): bool;
}
