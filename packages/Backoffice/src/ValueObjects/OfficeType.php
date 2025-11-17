<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum OfficeType: string
{
    case HEAD_OFFICE = 'head_office';
    case BRANCH = 'branch';
    case REGIONAL = 'regional';
    case SATELLITE = 'satellite';
    case VIRTUAL = 'virtual';

    public function isHeadOffice(): bool
    {
        return $this === self::HEAD_OFFICE;
    }

    public function requiresPhysicalAddress(): bool
    {
        return $this !== self::VIRTUAL;
    }
}
