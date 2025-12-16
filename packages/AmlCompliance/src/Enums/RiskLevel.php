<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Enums;

/**
 * AML Risk Level Classification
 * 
 * Risk levels based on FATF recommendations for risk-based approach.
 * Each level determines the intensity of due diligence required.
 */
enum RiskLevel: string
{
    /**
     * Low risk (score 0-39)
     * Standard due diligence applies
     */
    case LOW = 'low';

    /**
     * Medium risk (score 40-69)
     * Enhanced monitoring recommended
     */
    case MEDIUM = 'medium';

    /**
     * High risk (score 70-100)
     * Enhanced due diligence (EDD) required
     */
    case HIGH = 'high';

    /**
     * Get the risk level from a numeric score (0-100)
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score < 0 => self::LOW,
            $score <= 39 => self::LOW,
            $score <= 69 => self::MEDIUM,
            $score >= 70 => self::HIGH,
        };
    }

    /**
     * Get the minimum score threshold for this risk level
     */
    public function getMinScore(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 40,
            self::HIGH => 70,
        };
    }

    /**
     * Get the maximum score threshold for this risk level
     */
    public function getMaxScore(): int
    {
        return match ($this) {
            self::LOW => 39,
            self::MEDIUM => 69,
            self::HIGH => 100,
        };
    }

    /**
     * Check if this risk level requires Enhanced Due Diligence
     */
    public function requiresEdd(): bool
    {
        return $this === self::HIGH;
    }

    /**
     * Check if this risk level requires enhanced monitoring
     */
    public function requiresEnhancedMonitoring(): bool
    {
        return match ($this) {
            self::LOW => false,
            self::MEDIUM => true,
            self::HIGH => true,
        };
    }

    /**
     * Get recommended review frequency in days
     */
    public function getReviewFrequencyDays(): int
    {
        return match ($this) {
            self::LOW => 365,      // Annual review
            self::MEDIUM => 180,   // Semi-annual review
            self::HIGH => 90,      // Quarterly review
        };
    }

    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::LOW => 'Low risk - Standard due diligence applies',
            self::MEDIUM => 'Medium risk - Enhanced monitoring recommended',
            self::HIGH => 'High risk - Enhanced Due Diligence (EDD) required',
        };
    }

    /**
     * Get the severity weight for calculations
     */
    public function getSeverityWeight(): float
    {
        return match ($this) {
            self::LOW => 1.0,
            self::MEDIUM => 1.5,
            self::HIGH => 2.0,
        };
    }

    /**
     * Check if this risk level is higher than another
     */
    public function isHigherThan(self $other): bool
    {
        return $this->getMinScore() > $other->getMinScore();
    }

    /**
     * Check if this risk level is at least as high as another
     */
    public function isAtLeast(self $other): bool
    {
        return $this->getMinScore() >= $other->getMinScore();
    }

    /**
     * Get all risk levels in ascending order
     * 
     * @return array<self>
     */
    public static function ascending(): array
    {
        return [self::LOW, self::MEDIUM, self::HIGH];
    }
}
