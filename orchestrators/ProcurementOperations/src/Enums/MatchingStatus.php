<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Three-way matching result status.
 */
enum MatchingStatus: string
{
    case PENDING = 'pending';
    case MATCHED = 'matched';
    case PARTIAL_MATCH = 'partial_match';
    case MISMATCH = 'mismatch';
    case TOLERANCE_EXCEEDED = 'tolerance_exceeded';
    case APPROVED_WITH_VARIANCE = 'approved_with_variance';
    case REJECTED = 'rejected';

    /**
     * Check if status indicates successful match.
     */
    public function isMatched(): bool
    {
        return match ($this) {
            self::MATCHED, self::APPROVED_WITH_VARIANCE => true,
            default => false,
        };
    }

    /**
     * Check if status requires manual intervention.
     */
    public function requiresIntervention(): bool
    {
        return match ($this) {
            self::PARTIAL_MATCH, self::MISMATCH, self::TOLERANCE_EXCEEDED => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::MATCHED => 'Matched',
            self::PARTIAL_MATCH => 'Partial Match',
            self::MISMATCH => 'Mismatch',
            self::TOLERANCE_EXCEEDED => 'Tolerance Exceeded',
            self::APPROVED_WITH_VARIANCE => 'Approved with Variance',
            self::REJECTED => 'Rejected',
        };
    }
}
