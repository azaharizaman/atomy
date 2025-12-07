<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Payment processing workflow status.
 */
enum PaymentWorkflowStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SCHEDULED = 'scheduled';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Check if status is terminal.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::REJECTED, self::COMPLETED, self::FAILED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if payment can be retried.
     */
    public function canRetry(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Check if payment can be cancelled.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::DRAFT, self::PENDING_APPROVAL, self::APPROVED, self::SCHEDULED => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SCHEDULED => 'Scheduled',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
