<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum AggregationType: string
{
    case SUM = 'sum';
    case AVG = 'avg';
    case MIN = 'min';
    case MAX = 'max';
    case COUNT = 'count';
    case RATIO = 'ratio';
    case DELTA = 'delta';
    case ABSOLUTE_DELTA = 'absolute_delta';
    case PCT_CHANGE = 'pct_change';
    case WEIGHTED_AVG = 'weighted_avg';
    case WEIGHTED_SCORE = 'weighted_score';
    case ROLLING_AVG = 'rolling_avg';
    case ROLLING_SUM = 'rolling_sum';
    case PERIOD_COMPARE = 'period_compare';
}
