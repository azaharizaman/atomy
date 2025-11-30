<?php

declare(strict_types=1);

namespace Nexus\Payable\Enums;

/**
 * Payment method enum.
 */
enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CHEQUE = 'cheque';
    case CREDIT_CARD = 'credit_card';
    case CASH = 'cash';
    case ONLINE = 'online';
}
