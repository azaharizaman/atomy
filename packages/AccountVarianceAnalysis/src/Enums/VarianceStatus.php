<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Enums;

/**
 * Variance status for reporting.
 */
enum VarianceStatus: string
{
    case WITHIN_BUDGET = 'within_budget';
    case OVER_BUDGET = 'over_budget';
    case UNDER_BUDGET = 'under_budget';
    case REQUIRES_INVESTIGATION = 'requires_investigation';
}
