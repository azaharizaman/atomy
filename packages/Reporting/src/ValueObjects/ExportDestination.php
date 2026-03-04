<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

enum ExportDestination: string
{
    case STORAGE = 'storage';
}
