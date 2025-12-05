<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support addition operations.
 *
 * **When to Use:**
 * Implement this interface when a value object needs to support combining
 * two values of the same type. Common use cases include:
 * - Money values (adding amounts)
 * - Percentages (combining rates)
 * - Measurements (combining quantities)
 *
 * **Expected Behavior:**
 * - The add() operation MUST be immutable (return a new instance)
 * - The add() operation SHOULD only accept objects of the same type
 * - For Money, both operands MUST have the same currency
 * - The result type MUST be the same as the operand types
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements Addable
 * {
 *     public function add(Addable $other): static
 *     {
 *         if (!$other instanceof self) {
 *             throw new \InvalidArgumentException('Can only add Money');
 *         }
 *         $this->assertSameCurrency($other);
 *         return new self(
 *             $this->amountInMinorUnits + $other->amountInMinorUnits,
 *             $this->currency
 *         );
 *     }
 * }
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Common\ValueObjects\Percentage
 * @see Subtractable For the inverse operation
 */
interface Addable
{
    /**
     * Add another value object of the same type.
     *
     * @param self $other The value object to add
     * @return static New instance containing the sum (immutable)
     * @throws \InvalidArgumentException If $other is not compatible
     */
    public function add(self $other): static;
}
