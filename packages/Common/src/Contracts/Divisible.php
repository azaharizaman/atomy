<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support division.
 */
interface Divisible
{
    /**
     * Divide by a scalar value.
     * 
     * @param float|int $divisor
     * @return static New instance with the result
     */
    public function divide(float|int $divisor): static;
}
