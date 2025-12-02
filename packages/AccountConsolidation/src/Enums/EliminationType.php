<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Enums;

/**
 * Types of elimination entries.
 */
enum EliminationType: string
{
    case INTERCOMPANY_REVENUE = 'intercompany_revenue';
    case INTERCOMPANY_RECEIVABLE = 'intercompany_receivable';
    case INTERCOMPANY_DIVIDEND = 'intercompany_dividend';
    case INVESTMENT_ELIMINATION = 'investment_elimination';
    case UNREALIZED_PROFIT = 'unrealized_profit';
}
