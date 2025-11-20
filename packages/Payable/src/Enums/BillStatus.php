<?php

declare(strict_types=1);

namespace Nexus\Payable\Enums;

/**
 * Bill status enum.
 */
enum BillStatus: string
{
    case DRAFT = 'draft';
    case PENDING_MATCHING = 'pending_matching';
    case MATCHED = 'matched';
    case VARIANCE_REVIEW = 'variance_review';
    case APPROVED = 'approved';
    case POSTED = 'posted';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case CANCELLED = 'cancelled';
}
