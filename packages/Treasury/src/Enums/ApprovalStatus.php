<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

/**
 * Approval status for treasury transactions
 */
enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case REQUIRES_REVIEW = 'requires_review';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
            self::REQUIRES_REVIEW => 'Requires Review',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::CANCELLED, self::EXPIRED]);
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
