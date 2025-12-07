<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Goods Receipt workflow status.
 */
enum GoodsReceiptWorkflowStatus: string
{
    case DRAFT = 'draft';
    case PENDING_INSPECTION = 'pending_inspection';
    case INSPECTION_PASSED = 'inspection_passed';
    case INSPECTION_FAILED = 'inspection_failed';
    case POSTED = 'posted';
    case REVERSED = 'reversed';

    /**
     * Check if status is terminal.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::INSPECTION_FAILED, self::REVERSED => true,
            default => false,
        };
    }

    /**
     * Check if GR can be posted from this status.
     */
    public function canPost(): bool
    {
        return match ($this) {
            self::DRAFT, self::INSPECTION_PASSED => true,
            default => false,
        };
    }

    /**
     * Check if GR can be reversed from this status.
     */
    public function canReverse(): bool
    {
        return $this === self::POSTED;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_INSPECTION => 'Pending Inspection',
            self::INSPECTION_PASSED => 'Inspection Passed',
            self::INSPECTION_FAILED => 'Inspection Failed',
            self::POSTED => 'Posted',
            self::REVERSED => 'Reversed',
        };
    }
}
