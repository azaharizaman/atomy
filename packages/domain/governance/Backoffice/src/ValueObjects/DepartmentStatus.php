<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum DepartmentStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
