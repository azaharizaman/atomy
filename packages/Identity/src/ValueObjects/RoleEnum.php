<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * Native role enumeration for identity management.
 */
enum RoleEnum: string
{
    case TENANT_ADMIN = 'ROLE_TENANT_ADMIN';
    case ADMIN = 'ROLE_ADMIN';
    case USER = 'ROLE_USER';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * Get all role cases as an array of strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
