<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Enums;

/**
 * Account categories for financial statements.
 */
enum AccountCategory: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';
    case CONTRA_ASSET = 'contra_asset';
    case CONTRA_LIABILITY = 'contra_liability';
    case CONTRA_EQUITY = 'contra_equity';
}
