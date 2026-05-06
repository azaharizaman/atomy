<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum MetricResultStatus: string
{
    case AVAILABLE = 'available';
    case NO_DATA = 'no_data';
    case NOT_AVAILABLE = 'not_available';
    case ERROR = 'error';
}
