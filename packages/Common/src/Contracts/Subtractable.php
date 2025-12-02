<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support subtraction.
 */
interface Subtractable
{
    /**
     * Subtract another value object.
     * 
     * @return static New instance with the result
     */
    public function subtract(self $other): static;
}
