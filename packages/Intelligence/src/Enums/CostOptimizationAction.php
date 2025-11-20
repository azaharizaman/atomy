<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Enums;

/**
 * Cost optimization action
 */
enum CostOptimizationAction: string
{
    case DOWNGRADE_MODEL = 'downgrade_model';
    case REDUCE_FREQUENCY = 'reduce_frequency';
    case SWITCH_PROVIDER = 'switch_provider';
    case ENABLE_CACHING = 'enable_caching';
    case USE_RULE_BASED = 'use_rule_based';
}
