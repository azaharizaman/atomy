<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

/**
 * Investment type for short-term treasury investments
 */
enum InvestmentType: string
{
    case MONEY_MARKET = 'money_market';
    case TERM_DEPOSIT = 'term_deposit';
    case TREASURY_BILL = 'treasury_bill';
    case COMMERCIAL_PAPER = 'commercial_paper';
    case FIXED_DEPOSIT = 'fixed_deposit';
    case OVERNIGHT = 'overnight';

    public function label(): string
    {
        return match ($this) {
            self::MONEY_MARKET => 'Money Market',
            self::TERM_DEPOSIT => 'Term Deposit',
            self::TREASURY_BILL => 'Treasury Bill',
            self::COMMERCIAL_PAPER => 'Commercial Paper',
            self::FIXED_DEPOSIT => 'Fixed Deposit',
            self::OVERNIGHT => 'Overnight',
        };
    }

    public function isShortTerm(): bool
    {
        return in_array($this, [self::OVERNIGHT, self::MONEY_MARKET, self::TERM_DEPOSIT]);
    }
}
