<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Approval Status enum
 * 
 * Represents the status of a workflow approval request.
 */
enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Check if approval is finalized
     */
    public function isFinal(): bool
    {
        return match($this) {
            self::Approved, self::Rejected => true,
            self::Pending => false,
        };
    }

    /**
     * Check if approval was granted
     */
    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
