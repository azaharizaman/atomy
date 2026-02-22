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
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::PROPORTIONAL;
    }
}
