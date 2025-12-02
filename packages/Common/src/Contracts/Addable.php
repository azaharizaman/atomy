<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support addition.
 */
interface Addable
{
    /**
     * Add another value object.
     * 
     * @return static New instance with the result
     */
    public function add(self $other): static;
}
