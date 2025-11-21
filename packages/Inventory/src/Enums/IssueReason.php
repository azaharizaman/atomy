<?php

declare(strict_types=1);

namespace Nexus\Inventory\Enums;

/**
 * Stock issue reasons
 */
enum IssueReason: string
{
    case SALE = 'sale';
    case PRODUCTION = 'production';
    case ADJUSTMENT = 'adjustment';
    case TRANSFER = 'transfer';
    case DAMAGE = 'damage';
    case OBSOLESCENCE = 'obsolescence';
}
