<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Enums;

enum AccountType: string
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';
    case CREDIT = 'credit';
    case LOAN = 'loan';
    case INVESTMENT = 'investment';
    case MONEY_MARKET = 'money_market';
    case OTHER = 'other';
}
