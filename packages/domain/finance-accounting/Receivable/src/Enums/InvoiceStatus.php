<?php

declare(strict_types=1);

namespace Nexus\Receivable\Enums;

/**
 * Customer Invoice Status Lifecycle
 *
 * Tracks the state of an invoice from creation through payment or cancellation.
 */
enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case POSTED = 'posted';                    // GL journal entry posted
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case WRITTEN_OFF = 'written_off';          // Bad debt

    /**
     * Can this invoice be posted to the General Ledger?
     */
    public function canBePosted(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Can this invoice receive a payment?
     */
    public function canReceivePayment(): bool
    {
        return in_array($this, [self::POSTED, self::PARTIALLY_PAID, self::OVERDUE], true);
    }

    /**
     * Is this invoice in a final (immutable) state?
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED, self::WRITTEN_OFF], true);
    }

    /**
     * Can this invoice be voided/cancelled?
     */
    public function canBeVoided(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING_APPROVAL, self::APPROVED], true);
    }

    /**
     * Is this invoice overdue?
     */
    public function isOverdue(): bool
    {
        return $this === self::OVERDUE;
    }

    /**
     * Should this invoice contribute to customer outstanding balance?
     */
    public function contributesToBalance(): bool
    {
        return in_array($this, [self::POSTED, self::PARTIALLY_PAID, self::OVERDUE], true);
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::POSTED => 'Posted',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::WRITTEN_OFF => 'Written Off',
        };
    }
}
