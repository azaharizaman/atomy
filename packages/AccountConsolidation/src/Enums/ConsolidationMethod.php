<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Enums;

/**
 * Consolidation methods based on ownership level.
 */
enum ConsolidationMethod: string
{
    case FULL = 'full';
    case PROPORTIONAL = 'proportional';
    case EQUITY = 'equity';
    case COST = 'cost';
}
