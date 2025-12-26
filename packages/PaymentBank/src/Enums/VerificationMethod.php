<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Enums;

enum VerificationMethod: string
{
    case INSTANT = 'instant';
    case MICRO_DEPOSIT = 'micro_deposit';
    case DOCUMENT = 'document';
    case MANUAL_STATEMENT = 'manual_statement';
}
