<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support multiplication by a scalar.
 *
 * **When to Use:**
 * Implement this interface when a value object needs to be scaled
 * by a numeric factor. Common use cases include:
 * - Money values (applying markup, calculating totals: price × quantity)
 * - Percentages (combining rates)
 * - Measurements (scaling quantities)
 *
 * **Expected Behavior:**
 * - The multiply() operation MUST be immutable (return a new instance)
 * - The multiplier is a scalar (int or float), not another value object
 * - For Money, the result should be rounded appropriately (typically half-up)
 * - Multiplying by 0 SHOULD return a zero value of the same type
 * - Multiplying by 1 SHOULD return an equivalent value
 * - Negative multipliers are allowed (result sign flips)
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements Multipliable
 * {
 *     public function multiply(float|int $multiplier): static
 *     {
 *         $result = (int) round($this->amountInMinorUnits * $multiplier);
 *         return new self($result, $this->currency);
 *     }
 * }
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Common\ValueObjects\Percentage
 * @see Divisible For the inverse operation
 */
interface Multipliable
{
    /**
     * Multiply by a scalar value.
     *
     * @param float|int $multiplier The factor to multiply by
     * @return static New instance containing the product (immutable)
     */
    public function multiply(float|int $multiplier): static;
}
