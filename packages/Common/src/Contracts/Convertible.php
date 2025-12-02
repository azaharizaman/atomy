<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that can be converted between units.
 */
interface Convertible
{
    /**
     * Convert to another unit.
     * 
     * @param string $toUnit Target unit code
     * @return static New instance in the target unit
     */
    public function convertTo(string $toUnit): static;

    /**
     * Check if conversion to target unit is supported.
     */
    public function canConvertTo(string $toUnit): bool;
}
