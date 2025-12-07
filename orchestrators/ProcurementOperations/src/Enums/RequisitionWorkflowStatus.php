<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Requisition workflow status.
 */
enum RequisitionWorkflowStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CONVERTED_TO_PO = 'converted_to_po';
    case CANCELLED = 'cancelled';

    /**
     * Check if status is terminal.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::REJECTED, self::CONVERTED_TO_PO, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if requisition can be approved from this status.
     */
    public function canApprove(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    /**
     * Check if requisition can be converted to PO.
     */
    public function canConvertToPo(): bool
    {
        return $this === self::APPROVED;
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
            self::APPROVED => [self::CONVERTED_TO_PO, self::CANCELLED],
            self::REJECTED => [],
            self::CONVERTED_TO_PO => [],
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
            self::CONVERTED_TO_PO => 'Converted to PO',
            self::CANCELLED => 'Cancelled',
        };
    }
}
