<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that can be compared.
 */
interface Comparable
{
    /**
     * Compare this object with another.
     * 
     * @return int Returns -1 if less than, 0 if equal, 1 if greater than
     */
    public function compareTo(self $other): int;

    /**
     * Check if equal to another object.
     */
    public function equals(self $other): bool;

    /**
     * Check if greater than another object.
     */
    public function greaterThan(self $other): bool;

    /**
     * Check if less than another object.
     */
    public function lessThan(self $other): bool;
}
