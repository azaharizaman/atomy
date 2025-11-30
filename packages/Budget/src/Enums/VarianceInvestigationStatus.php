<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Variance Investigation Status enum
 * 
 * Tracks the status of budget variance investigations.
 */
enum VarianceInvestigationStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';

    /**
     * Check if investigation is complete
     */
    public function isComplete(): bool
    {
        return $this === self::Resolved;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending Investigation',
            self::UnderReview => 'Under Review',
            self::Resolved => 'Resolved',
        };
    }
}
