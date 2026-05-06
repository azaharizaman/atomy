<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum PeriodGranularity: string
{
    case DATE = 'date';
    case MONTH = 'month';
    case QUARTER = 'quarter';
    case YEAR = 'year';
}
