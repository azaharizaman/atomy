<?php

declare(strict_types=1);

namespace Nexus\Tenant\Enums;

/**
 * Supported tenant plans.
 */
enum TenantPlan: string
{
    case Starter = 'starter';
    case Professional = 'professional';
    case Enterprise = 'enterprise';

    /**
     * Get all plan cases as an array of strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
