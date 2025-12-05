<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support division by a scalar.
 *
 * **When to Use:**
 * Implement this interface when a value object needs to be divided
 * by a numeric factor. Common use cases include:
 * - Money values (splitting bills, calculating unit prices)
 * - Percentages (distributing portions)
 * - Measurements (dividing quantities)
 *
 * **Expected Behavior:**
 * - The divide() operation MUST be immutable (return a new instance)
 * - The divisor is a scalar (int or float), not another value object
 * - Division by zero MUST throw an exception
 * - For Money, the result should be rounded appropriately (typically half-up)
 * - Dividing by 1 SHOULD return an equivalent value
 * - Negative divisors are allowed (result sign flips)
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements Divisible
 * {
 *     public function divide(float|int $divisor): static
 *     {
 *         if ($divisor == 0) {
 *             throw new InvalidMoneyException('Cannot divide by zero');
 *         }
 *         $result = (int) round($this->amountInMinorUnits / $divisor);
 *         return new self($result, $this->currency);
 *     }
 * }
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Common\ValueObjects\Percentage
 * @see Multipliable For the inverse operation
 */
interface Divisible
{
    /**
     * Divide by a scalar value.
     *
     * @param float|int $divisor The factor to divide by (must not be zero)
     * @return static New instance containing the quotient (immutable)
     * @throws \InvalidArgumentException|\DomainException If divisor is zero
     */
    public function divide(float|int $divisor): static;
}
