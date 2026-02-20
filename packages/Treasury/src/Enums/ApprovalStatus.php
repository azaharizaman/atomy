<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

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

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    public function requiresReview(): bool
    {
        return $this === self::REQUIRES_REVIEW;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::CANCELLED, self::EXPIRED], true);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::APPROVED, self::REJECTED, self::CANCELLED, self::REQUIRES_REVIEW], true),
            self::REQUIRES_REVIEW => in_array($status, [self::APPROVED, self::REJECTED, self::CANCELLED, self::PENDING], true),
            self::APPROVED, self::REJECTED, self::CANCELLED, self::EXPIRED => false,
        };
    }
}
