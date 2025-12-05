<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support subtraction operations.
 *
 * **When to Use:**
 * Implement this interface when a value object needs to support removing
 * a value of the same type. Common use cases include:
 * - Money values (calculating differences, discounts)
 * - Percentages (removing portions)
 * - Measurements (calculating remaining quantities)
 *
 * **Expected Behavior:**
 * - The subtract() operation MUST be immutable (return a new instance)
 * - The subtract() operation SHOULD only accept objects of the same type
 * - For Money, both operands MUST have the same currency
 * - Results MAY be negative (e.g., overdraft, deficit)
 * - The result type MUST be the same as the operand types
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements Subtractable
 * {
 *     public function subtract(Subtractable $other): static
 *     {
 *         if (!$other instanceof self) {
 *             throw new \InvalidArgumentException('Can only subtract Money');
 *         }
 *         $this->assertSameCurrency($other);
 *         return new self(
 *             $this->amountInMinorUnits - $other->amountInMinorUnits,
 *             $this->currency
 *         );
 *     }
 * }
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Common\ValueObjects\Percentage
 * @see Addable For the inverse operation
 */
interface Subtractable
{
    /**
     * Subtract another value object of the same type.
     *
     * @param self $other The value object to subtract
     * @return static New instance containing the difference (immutable)
     * @throws \InvalidArgumentException If $other is not compatible
     */
    public function subtract(self $other): static;
}
