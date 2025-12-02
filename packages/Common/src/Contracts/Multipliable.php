<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support multiplication.
 */
interface Multipliable
{
    /**
     * Multiply by a scalar value.
     * 
     * @param float|int $multiplier
     * @return static New instance with the result
     */
    public function multiply(float|int $multiplier): static;
}
