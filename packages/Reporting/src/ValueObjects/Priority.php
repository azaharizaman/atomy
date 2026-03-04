<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

enum Priority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
}
