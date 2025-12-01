<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Enums;

/**
 * Types of financial statements.
 */
enum StatementType: string
{
    case BALANCE_SHEET = 'balance_sheet';
    case INCOME_STATEMENT = 'income_statement';
    case CASH_FLOW_STATEMENT = 'cash_flow_statement';
    case STATEMENT_OF_CHANGES_IN_EQUITY = 'statement_of_changes_in_equity';
    case NOTES_TO_FINANCIAL_STATEMENTS = 'notes_to_financial_statements';
    case TRIAL_BALANCE = 'trial_balance';
}
