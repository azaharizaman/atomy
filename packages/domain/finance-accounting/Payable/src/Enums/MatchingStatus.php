<?php

declare(strict_types=1);

namespace Nexus\Payable\Enums;

/**
 * Matching status enum.
 */
enum MatchingStatus: string
{
    case PENDING = 'pending';
    case MATCHED = 'matched';
    case VARIANCE_REVIEW = 'variance_review';
    case FAILED = 'failed';
    case OVERRIDDEN = 'overridden';
}
