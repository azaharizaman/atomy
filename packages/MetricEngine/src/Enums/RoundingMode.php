<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum RoundingMode: string
{
    case HALF_UP = 'half_up';
    case HALF_DOWN = 'half_down';
    case HALF_EVEN = 'half_even';
    case HALF_ODD = 'half_odd';
    case TOWARD_ZERO = 'toward_zero';
    case AWAY_FROM_ZERO = 'away_from_zero';
}
