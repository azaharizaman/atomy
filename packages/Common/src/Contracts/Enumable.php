<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects representing enumerated values.
 */
interface Enumable
{
    /**
     * Get all possible values.
     * 
     * @return array<static>
     */
    public static function values(): array;

    /**
     * Get the value/code.
     */
    public function getValue(): string;

    /**
     * Check if this value is valid.
     */
    public static function isValid(string $value): bool;
}
