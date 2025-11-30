<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Leave request status value object.
 */
enum LeaveStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function isFinal(): bool
    {
        return match($this) {
            self::PENDING => false,
            self::APPROVED, self::REJECTED, self::CANCELLED => true,
        };
    }
}
