<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum CompanyStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case DISSOLVED = 'dissolved';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canHaveActiveChildren(): bool
    {
        return $this === self::ACTIVE;
    }
}
