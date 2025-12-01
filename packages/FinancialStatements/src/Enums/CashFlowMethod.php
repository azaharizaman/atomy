<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Enums;

/**
 * Methods for presenting cash flow statement.
 */
enum CashFlowMethod: string
{
    case DIRECT = 'direct';
    case INDIRECT = 'indirect';
}
