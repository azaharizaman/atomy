<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Purchase Order workflow status.
 */
enum PurchaseOrderWorkflowStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SENT_TO_VENDOR = 'sent_to_vendor';
    case ACKNOWLEDGED = 'acknowledged';
    case PARTIALLY_RECEIVED = 'partially_received';
    case FULLY_RECEIVED = 'fully_received';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    /**
     * Check if status is terminal.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::REJECTED, self::CLOSED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if PO can receive goods.
     */
    public function canReceiveGoods(): bool
    {
        return match ($this) {
            self::SENT_TO_VENDOR, self::ACKNOWLEDGED, self::PARTIALLY_RECEIVED => true,
            default => false,
        };
    }

    /**
     * Check if PO can be cancelled.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::DRAFT, self::PENDING_APPROVAL, self::APPROVED, self::SENT_TO_VENDOR => true,
            default => false,
        };
    }

    /**
     * Get next valid statuses from current status.
     *
     * @return array<self>
     */
    public function getNextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING_APPROVAL, self::CANCELLED],
            self::PENDING_APPROVAL => [self::APPROVED, self::REJECTED],
            self::APPROVED => [self::SENT_TO_VENDOR, self::CANCELLED],
            self::SENT_TO_VENDOR => [self::ACKNOWLEDGED, self::PARTIALLY_RECEIVED, self::CANCELLED],
            self::ACKNOWLEDGED => [self::PARTIALLY_RECEIVED, self::FULLY_RECEIVED],
            self::PARTIALLY_RECEIVED => [self::PARTIALLY_RECEIVED, self::FULLY_RECEIVED],
            self::FULLY_RECEIVED => [self::CLOSED],
            self::REJECTED => [],
            self::CLOSED => [],
            self::CANCELLED => [],
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
            self::SENT_TO_VENDOR => 'Sent to Vendor',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::FULLY_RECEIVED => 'Fully Received',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
