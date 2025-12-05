<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that can be formatted for display.
 *
 * **When to Use:**
 * Implement this interface when value objects need human-readable
 * display formatting with customizable options. Common use cases:
 * - Money display (with/without currency symbol, decimal places)
 * - Percentages (with % symbol, decimal precision)
 * - Dates and date ranges (various format patterns)
 * - Measurements (with units, precision)
 *
 * **Expected Behavior:**
 * - format() returns a human-readable string
 * - Options array allows customization (implementation-specific)
 * - Default options should produce sensible output
 * - Format should NOT change the underlying value
 *
 * **Common Options (implementation-specific):**
 * - `decimals`: Number of decimal places (int)
 * - `symbol`: Include currency/unit symbol (bool)
 * - `locale`: Localization settings (string)
 * - `pattern`: Custom format pattern (string)
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements Formattable
 * {
 *     public function format(array $options = []): string
 *     {
 *         $decimals = $options['decimals'] ?? 2;
 *         $symbol = $options['symbol'] ?? true;
 *
 *         $divisor = 10 ** $decimals;
 *         $formatted = number_format(
 *             $this->amountInMinorUnits / $divisor,
 *             $decimals,
 *             '.',
 *             ','
 *         );
 *
 *         return $symbol
 *             ? $this->currency . ' ' . $formatted
 *             : $formatted;
 *     }
 * }
 * ```
 *
 * **Usage:**
 * ```php
 * $money = Money::of(10050, 'MYR');
 *
 * // Default format: "MYR 100.50"
 * echo $money->format();
 *
 * // Without symbol: "100.50"
 * echo $money->format(['symbol' => false]);
 *
 * // With 4 decimal places: "MYR 100.5000"
 * echo $money->format(['decimals' => 4]);
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Common\ValueObjects\Percentage
 */
interface Formattable
{
    /**
     * Format the value object for human-readable display.
     *
     * @param array<string, mixed> $options Implementation-specific formatting options
     * @return string Formatted human-readable string
     */
    public function format(array $options = []): string;
}
