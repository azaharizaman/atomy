<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * Forecast Confidence enum.
 *
 * Defines confidence levels for demand forecasts.
 */
enum ForecastConfidence: string
{
    /**
     * High confidence - ML model with strong historical correlation.
     */
    case HIGH = 'high';

    /**
     * Medium confidence - ML model or good historical data.
     */
    case MEDIUM = 'medium';

    /**
     * Low confidence - limited data or high variability.
     */
    case LOW = 'low';

    /**
     * Fallback - using historical average due to ML unavailability.
     */
    case FALLBACK = 'fallback';

    /**
     * Unknown - insufficient data to determine confidence.
     */
    case UNKNOWN = 'unknown';

    /**
     * Get confidence score (0.0 to 1.0).
     */
    public function getScore(): float
    {
        return match ($this) {
            self::HIGH => 0.9,
            self::MEDIUM => 0.7,
            self::LOW => 0.5,
            self::FALLBACK => 0.4,
            self::UNKNOWN => 0.2,
        };
    }

    /**
     * Get recommended safety stock multiplier.
     */
    public function getSafetyStockMultiplier(): float
    {
        return match ($this) {
            self::HIGH => 1.0,
            self::MEDIUM => 1.2,
            self::LOW => 1.5,
            self::FALLBACK => 1.8,
            self::UNKNOWN => 2.0,
        };
    }

    /**
     * Check if forecast should be reviewed manually.
     */
    public function requiresReview(): bool
    {
        return match ($this) {
            self::HIGH, self::MEDIUM => false,
            self::LOW, self::FALLBACK, self::UNKNOWN => true,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'High Confidence',
            self::MEDIUM => 'Medium Confidence',
            self::LOW => 'Low Confidence',
            self::FALLBACK => 'Fallback (Historical)',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get description.
     */
    public function description(): string
    {
        return match ($this) {
            self::HIGH => 'Strong ML prediction with good historical correlation',
            self::MEDIUM => 'Reliable prediction from ML or historical data',
            self::LOW => 'Limited data or high demand variability',
            self::FALLBACK => 'Using historical average - ML service unavailable',
            self::UNKNOWN => 'Insufficient data to determine confidence',
        };
    }

    /**
     * Get confidence from numeric score.
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 0.85 => self::HIGH,
            $score >= 0.65 => self::MEDIUM,
            $score >= 0.45 => self::LOW,
            $score >= 0.30 => self::FALLBACK,
            default => self::UNKNOWN,
        };
    }
}
