<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Enums;

enum ConsentStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case PENDING = 'pending';
    case REAUTH_REQUIRED = 'reauth_required';
}
