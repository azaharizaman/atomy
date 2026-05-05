<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum WindowType: string
{
    case FIXED_ROLLING = 'fixed_rolling';
    case EXPLICIT_RANGE = 'explicit_range';
}
