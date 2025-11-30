<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum TransferStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

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

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::REJECTED, self::CANCELLED, self::COMPLETED => true,
            default => false,
        };
    }
}
