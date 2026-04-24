<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Enums;

enum SourceLineOrigin: string
{
    case PROVIDER = 'provider';
    case DETERMINISTIC = 'deterministic';
    case MANUAL = 'manual';
}
