<?php

declare(strict_types=1);

namespace Nexus\Inventory\Enums;

/**
 * Inventory valuation methods
 */
enum ValuationMethod: string
{
    case FIFO = 'fifo';
    case WEIGHTED_AVERAGE = 'weighted_average';
    case STANDARD_COST = 'standard_cost';
}
