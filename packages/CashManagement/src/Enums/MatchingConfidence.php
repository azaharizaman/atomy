<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Enums;

/**
 * Matching Confidence Enumeration
 *
 * Indicates the confidence level of an automatic transaction match.
 */
enum MatchingConfidence: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case MANUAL = 'manual';

    /**
     * Get numeric confidence score (0-100)
     */
    public function score(): int
    {
        return match ($this) {
            self::HIGH => 95,
            self::MEDIUM => 70,
            self::LOW => 40,
            self::MANUAL => 100,
        };
    }

    /**
     * Check if confidence is sufficient for auto-processing
     */
    public function isHighConfidence(): bool
    {
        return $this === self::HIGH || $this === self::MANUAL;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'High Confidence',
            self::MEDIUM => 'Medium Confidence',
            self::LOW => 'Low Confidence',
            self::MANUAL => 'Manual Match',
        };
    }
}
