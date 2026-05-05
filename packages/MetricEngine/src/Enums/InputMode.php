<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum InputMode: string
{
    case SCALAR = 'scalar';
    case TIME_SERIES = 'time_series';
}
