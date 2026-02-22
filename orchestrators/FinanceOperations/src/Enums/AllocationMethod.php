<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Enums;

/**
 * Enum representing cost allocation methods.
 *
 * @since 1.0.0
 */
enum AllocationMethod: string
{
    case PROPORTIONAL = 'proportional';
    case EQUAL = 'equal';
    case MANUAL = 'manual';

    /**
     * Create AllocationMethod from a string value.
     *
     * @param string $value The string value to convert
     * @return self The corresponding AllocationMethod case
     * @throws \InvalidArgumentException If the value cannot be matched
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower($value)) ?? self::PROPORTIONAL;
    }
}
