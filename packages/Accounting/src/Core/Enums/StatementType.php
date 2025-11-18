<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Enums;

/**
 * Financial statement types.
 */
enum StatementType: string
{
    case BALANCE_SHEET = 'balance_sheet';
    case INCOME_STATEMENT = 'income_statement';
    case CASH_FLOW = 'cash_flow';
    case CHANGES_IN_EQUITY = 'changes_in_equity';
    case TRIAL_BALANCE = 'trial_balance';

    /**
     * Get the display name.
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::BALANCE_SHEET => 'Balance Sheet',
            self::INCOME_STATEMENT => 'Income Statement',
            self::CASH_FLOW => 'Cash Flow Statement',
            self::CHANGES_IN_EQUITY => 'Statement of Changes in Equity',
            self::TRIAL_BALANCE => 'Trial Balance',
        };
    }

    /**
     * Check if this is a point-in-time statement.
     */
    public function isPointInTime(): bool
    {
        return match($this) {
            self::BALANCE_SHEET, self::TRIAL_BALANCE => true,
            self::INCOME_STATEMENT, self::CASH_FLOW, self::CHANGES_IN_EQUITY => false,
        };
    }

    /**
     * Check if this is a period statement.
     */
    public function isPeriodStatement(): bool
    {
        return !$this->isPointInTime();
    }
}
