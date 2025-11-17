<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum StaffType: string
{
    case PERMANENT = 'permanent';
    case CONTRACT = 'contract';
    case TEMPORARY = 'temporary';
    case INTERN = 'intern';
    case CONSULTANT = 'consultant';

    public function isPermanent(): bool
    {
        return $this === self::PERMANENT;
    }

    public function isContractual(): bool
    {
        return match ($this) {
            self::CONTRACT, self::TEMPORARY, self::CONSULTANT => true,
            default => false,
        };
    }
}
