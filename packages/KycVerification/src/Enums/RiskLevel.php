<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Enums;

/**
 * Risk level classification for KYC verification.
 * 
 * Determines the level of due diligence required.
 */
enum RiskLevel: string
{
    /**
     * Low risk - Standard CDD applies
     */
    case LOW = 'low';

    /**
     * Medium risk - Enhanced monitoring
     */
    case MEDIUM = 'medium';

    /**
     * High risk - Enhanced Due Diligence (EDD) required
     */
    case HIGH = 'high';

    /**
     * Very high risk - Prohibited or requires senior approval
     */
    case VERY_HIGH = 'very_high';

    /**
     * Prohibited - Cannot proceed with relationship
     */
    case PROHIBITED = 'prohibited';

    /**
     * Get the score threshold for this risk level
     */
    public function scoreThreshold(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 30,
            self::HIGH => 60,
            self::VERY_HIGH => 80,
            self::PROHIBITED => 95,
        };
    }

    /**
     * Create risk level from score (0-100)
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 95 => self::PROHIBITED,
            $score >= 80 => self::VERY_HIGH,
            $score >= 60 => self::HIGH,
            $score >= 30 => self::MEDIUM,
            default => self::LOW,
        };
    }

    /**
     * Check if this risk level requires Enhanced Due Diligence (EDD)
     */
    public function requiresEdd(): bool
    {
        return in_array($this, [
            self::HIGH,
            self::VERY_HIGH,
            self::PROHIBITED,
        ], true);
    }

    /**
     * Check if this risk level requires senior approval
     */
    public function requiresSeniorApproval(): bool
    {
        return in_array($this, [
            self::VERY_HIGH,
            self::PROHIBITED,
        ], true);
    }

    /**
     * Check if transactions are blocked at this risk level
     */
    public function blocksTransactions(): bool
    {
        return $this === self::PROHIBITED;
    }

    /**
     * Get review frequency in days
     */
    public function reviewFrequencyDays(): int
    {
        return match ($this) {
            self::LOW => 365,        // Annual
            self::MEDIUM => 180,     // Semi-annual
            self::HIGH => 90,        // Quarterly
            self::VERY_HIGH => 30,   // Monthly
            self::PROHIBITED => 0,    // Continuous/blocked
        };
    }

    /**
     * Get required due diligence level
     */
    public function dueDiligenceLevel(): DueDiligenceLevel
    {
        return match ($this) {
            self::LOW => DueDiligenceLevel::SIMPLIFIED,
            self::MEDIUM => DueDiligenceLevel::STANDARD,
            self::HIGH, self::VERY_HIGH => DueDiligenceLevel::ENHANCED,
            self::PROHIBITED => DueDiligenceLevel::ENHANCED,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low Risk',
            self::MEDIUM => 'Medium Risk',
            self::HIGH => 'High Risk',
            self::VERY_HIGH => 'Very High Risk',
            self::PROHIBITED => 'Prohibited',
        };
    }

    /**
     * Get severity for display purposes
     */
    public function severity(): string
    {
        return match ($this) {
            self::LOW => 'success',
            self::MEDIUM => 'warning',
            self::HIGH => 'danger',
            self::VERY_HIGH => 'danger',
            self::PROHIBITED => 'critical',
        };
    }
}
