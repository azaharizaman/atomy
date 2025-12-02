<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support statistical operations.
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
