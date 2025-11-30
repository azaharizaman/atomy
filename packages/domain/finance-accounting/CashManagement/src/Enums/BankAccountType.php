<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Enums;

/**
 * Bank Account Type Enumeration
 *
 * Defines the types of bank accounts supported in the system.
 */
enum BankAccountType: string
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';
    case CREDIT_CARD = 'credit_card';
    case MONEY_MARKET = 'money_market';
    case LINE_OF_CREDIT = 'line_of_credit';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::CHECKING => 'Checking Account',
            self::SAVINGS => 'Savings Account',
            self::CREDIT_CARD => 'Credit Card',
            self::MONEY_MARKET => 'Money Market',
            self::LINE_OF_CREDIT => 'Line of Credit',
        };
    }
}
