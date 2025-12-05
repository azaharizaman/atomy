<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support comparison operations.
 *
 * **When to Use:**
 * Implement this interface when value objects need to be compared for
 * equality, ordering, or ranking. Essential for:
 * - Sorting collections of value objects
 * - Finding min/max values
 * - Equality checks in business logic
 * - Validation (e.g., "amount must be greater than minimum")
 *
 * **Expected Behavior:**
 * - compareTo() MUST return -1 if less than, 0 if equal, 1 if greater than
 * - equals() MUST return true only when values are semantically identical
 * - greaterThan() and lessThan() MUST be consistent with compareTo()
 * - Comparison SHOULD only work with same-type objects
 * - For Money: comparison across currencies MUST throw an exception
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements Comparable
 * {
 *     public function compareTo(Comparable $other): int
 *     {
 *         if (!$other instanceof self) {
 *             throw new \InvalidArgumentException('Can only compare with Money');
 *         }
 *         $this->assertSameCurrency($other);
 *         return $this->amountInMinorUnits <=> $other->amountInMinorUnits;
 *     }
 *
 *     public function equals(Comparable $other): bool
 *     {
 *         return $this->compareTo($other) === 0;
 *     }
 *
 *     public function greaterThan(Comparable $other): bool
 *     {
 *         return $this->compareTo($other) > 0;
 *     }
 *
 *     public function lessThan(Comparable $other): bool
 *     {
 *         return $this->compareTo($other) < 0;
 *     }
 * }
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Common\ValueObjects\DateRange
 */
interface Comparable
{
    /**
     * Compare this object with another of the same type.
     *
     * @param self $other The object to compare against
     * @return int Returns -1 if less than, 0 if equal, 1 if greater than
     * @throws \InvalidArgumentException If comparison is not possible
     */
    public function compareTo(self $other): int;

    /**
     * Check if this object is equal to another.
     *
     * @param self $other The object to compare against
     * @return bool True if values are semantically equal
     */
    public function equals(self $other): bool;

    /**
     * Check if this object is greater than another.
     *
     * @param self $other The object to compare against
     * @return bool True if this is greater than $other
     */
    public function greaterThan(self $other): bool;

    /**
     * Check if this object is less than another.
     *
     * @param self $other The object to compare against
     * @return bool True if this is less than $other
     */
    public function lessThan(self $other): bool;
}
