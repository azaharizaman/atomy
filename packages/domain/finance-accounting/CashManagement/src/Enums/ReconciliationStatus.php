<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Enums;

/**
 * Reconciliation Status Enumeration
 *
 * Defines the lifecycle status of a bank reconciliation.
 */
enum ReconciliationStatus: string
{
    case PENDING = 'pending';
    case MATCHED = 'matched';
    case VARIANCE_REVIEW = 'variance_review';
    case RECONCILED = 'reconciled';
    case UNMATCHED = 'unmatched';
    case REJECTED = 'rejected';

    /**
     * Check if status is final (no further action required)
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::RECONCILED, self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Check if status requires user attention
     */
    public function requiresAction(): bool
    {
        return match ($this) {
            self::VARIANCE_REVIEW, self::UNMATCHED => true,
            default => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::MATCHED => 'Matched',
            self::VARIANCE_REVIEW => 'Variance Review Required',
            self::RECONCILED => 'Reconciled',
            self::UNMATCHED => 'Unmatched',
            self::REJECTED => 'Rejected',
        };
    }
}
