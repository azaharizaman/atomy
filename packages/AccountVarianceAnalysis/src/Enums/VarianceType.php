<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Enums;

/**
 * Types of variance.
 */
enum VarianceType: string
{
    case FAVORABLE = 'favorable';
    case UNFAVORABLE = 'unfavorable';
    case NEUTRAL = 'neutral';
}
