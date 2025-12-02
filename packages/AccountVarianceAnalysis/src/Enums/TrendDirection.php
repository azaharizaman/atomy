<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Enums;

/**
 * Trend direction.
 */
enum TrendDirection: string
{
    case INCREASING = 'increasing';
    case DECREASING = 'decreasing';
    case STABLE = 'stable';
    case VOLATILE = 'volatile';
}
