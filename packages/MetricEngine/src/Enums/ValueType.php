<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum ValueType: string
{
    case NUMBER = 'number';
    case SERIES = 'series';
    case COMPARISON = 'comparison';
}
