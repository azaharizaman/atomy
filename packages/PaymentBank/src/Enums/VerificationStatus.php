<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Enums;

enum VerificationStatus: string
{
    case PENDING = 'pending';
    case PENDING_MICRO_DEPOSITS = 'pending_micro_deposits';
    case AWAITING_CONFIRMATION = 'awaiting_confirmation';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
}
