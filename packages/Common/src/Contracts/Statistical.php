<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support statistical operations.
 *
 * Provides statistical computation capabilities for numeric value objects,
 * enabling calculations like averages, absolute values, and range checks.
 *
 * **When to Use:**
 * - Numeric value objects needing statistical analysis (Money, Quantity, Score)
 * - Financial calculations requiring averaging or bounds checking
 * - Any measurable value object that benefits from statistical operations
 * - Performance metrics, KPIs, or scoring systems
 *
 * **Expected Behavior:**
 * - `average()` calculates arithmetic mean, returns new instance
 * - `abs()` returns absolute value as new instance (immutability)
 * - `isWithinRange()` performs inclusive bounds checking
 * - Empty arrays to `average()` should throw exception
 * - Operations must maintain precision (use bcmath for Money)
 *
 * @example Example implementation for Score:
 * ```php
 * final readonly class Score implements Statistical
 * {
 *     public function __construct(private float $value) {}
 *
 *     public static function average(array $values): static
 *     {
 *         if (empty($values)) {
 *             throw new \InvalidArgumentException('Cannot average empty array');
 *         }
 *
 *         $sum = array_reduce(
 *             $values,
 *             fn(float $carry, Score $score) => $carry + $score->value,
 *             0.0
 *         );
 *
 *         return new self($sum / count($values));
 *     }
 *
 *     public function abs(): static
 *     {
 *         return new self(abs($this->value));
 *     }
 *
 *     public function isWithinRange(Statistical $min, Statistical $max): bool
 *     {
 *         return $this->value >= $min->value && $this->value <= $max->value;
 *     }
 * }
 *
 * // Usage
 * $scores = [new Score(85), new Score(92), new Score(78)];
 * $avgScore = Score::average($scores); // Score(85.0)
 *
 * $negativeScore = new Score(-15);
 * $absScore = $negativeScore->abs(); // Score(15)
 *
 * $passing = $avgScore->isWithinRange(new Score(60), new Score(100)); // true
 * ```
 */
interface Statistical
{
    /**
     * Calculate average of multiple values.
     * 
     * @param array<static> $values
     * @return static
     */
    public static function average(array $values): static;

    /**
     * Get absolute value.
     * 
     * @return static
     */
    public function abs(): static;

    /**
     * Check if value is within a range.
     */
    public function isWithinRange(self $min, self $max): bool;
}
