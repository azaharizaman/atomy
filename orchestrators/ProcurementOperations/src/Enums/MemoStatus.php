<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Status of a credit/debit memo.
 */
enum MemoStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case APPLIED = 'applied';
    case PARTIALLY_APPLIED = 'partially_applied';
    case CANCELLED = 'cancelled';

    /**
     * Check if memo can be edited.
     */
    public function canEdit(): bool
    {
        return match ($this) {
            self::DRAFT => true,
            default => false,
        };
    }

    /**
     * Check if memo can be applied to invoices.
     */
    public function canApply(): bool
    {
        return match ($this) {
            self::APPROVED,
            self::PARTIALLY_APPLIED => true,
            default => false,
        };
    }

    /**
     * Check if memo can be cancelled.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::DRAFT,
            self::PENDING_APPROVAL,
            self::APPROVED => true,
            default => false,
        };
    }

    /**
     * Check if this is a terminal status.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::APPLIED,
            self::REJECTED,
            self::CANCELLED => true,
            default => false,
        };
    }
}
