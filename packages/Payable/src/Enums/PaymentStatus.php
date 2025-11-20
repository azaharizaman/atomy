<?php

declare(strict_types=1);

namespace Nexus\Payable\Enums;

/**
 * Payment status enum.
 */
enum PaymentStatus: string
{
    case SCHEDULED = 'scheduled';
    case APPROVED = 'approved';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case RECONCILED = 'reconciled';
    case FAILED = 'failed';
    case VOIDED = 'voided';
}
