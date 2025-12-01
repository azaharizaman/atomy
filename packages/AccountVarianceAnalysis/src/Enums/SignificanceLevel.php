<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Enums;

/**
 * Significance levels for variance evaluation.
 */
enum SignificanceLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}
