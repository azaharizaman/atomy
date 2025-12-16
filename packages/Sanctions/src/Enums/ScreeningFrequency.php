<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Enums;

/**
 * Frequency for periodic sanctions re-screening
 * 
 * Based on risk-based approach and regulatory requirements
 */
enum ScreeningFrequency: string
{
    /**
     * Daily screening (highest risk)
     * - High-value transactions
     * - High-risk jurisdictions
     * - PEPs with HIGH level
     * - Critical compliance requirements
     */
    case DAILY = 'daily';
    
    /**
     * Weekly screening (elevated risk)
     * - Medium-high risk profiles
     * - Active trading accounts
     * - Medium-level PEPs
     * - Regular monitoring required
     */
    case WEEKLY = 'weekly';
    
    /**
     * Monthly screening (moderate risk)
     * - Standard risk profiles
     * - Regular business relationships
     * - Low-level PEPs
     * - Routine compliance
     */
    case MONTHLY = 'monthly';
    
    /**
     * Quarterly screening (low risk)
     * - Low-risk profiles
     * - Dormant accounts
     * - Former PEPs
     * - Minimum compliance requirements
     */
    case QUARTERLY = 'quarterly';
    
    /**
     * Semi-annual screening
     * - Very low-risk profiles
     * - Minimal activity accounts
     * - Cost-optimized compliance
     */
    case SEMI_ANNUAL = 'semi_annual';
    
    /**
     * Annual screening (minimum frequency)
     * - Lowest risk profiles
     * - Inactive accounts
     * - Regulatory minimum
     */
    case ANNUAL = 'annual';
    
    /**
     * Real-time screening (transaction-based)
     * - Every transaction screened
     * - High-risk entities
     * - Maximum compliance
     */
    case REAL_TIME = 'real_time';
    
    /**
     * Get frequency in days
     */
    public function getDays(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::SEMI_ANNUAL => 180,
            self::ANNUAL => 365,
            self::REAL_TIME => 0, // Immediate
        };
    }
    
    /**
     * Get frequency in hours
     */
    public function getHours(): int
    {
        return match ($this) {
            self::DAILY => 24,
            self::WEEKLY => 168,
            self::MONTHLY => 720,
            self::QUARTERLY => 2160,
            self::SEMI_ANNUAL => 4320,
            self::ANNUAL => 8760,
            self::REAL_TIME => 0,
        };
    }
    
    /**
     * Check if this is real-time screening
     */
    public function isRealTime(): bool
    {
        return $this === self::REAL_TIME;
    }
    
    /**
     * Get recommended frequency for risk level
     */
    public static function fromRiskLevel(string $riskLevel): self
    {
        return match (strtoupper($riskLevel)) {
            'CRITICAL', 'VERY_HIGH' => self::DAILY,
            'HIGH' => self::WEEKLY,
            'MEDIUM' => self::MONTHLY,
            'LOW' => self::QUARTERLY,
            'VERY_LOW' => self::SEMI_ANNUAL,
            'MINIMAL' => self::ANNUAL,
            default => self::MONTHLY,
        };
    }
    
    /**
     * Get recommended frequency for PEP level
     */
    public static function fromPepLevel(PepLevel $pepLevel): self
    {
        return match ($pepLevel) {
            PepLevel::HIGH => self::DAILY,
            PepLevel::MEDIUM => self::WEEKLY,
            PepLevel::LOW => self::MONTHLY,
            PepLevel::NONE => self::QUARTERLY,
        };
    }
    
    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => 'Daily (24 hours)',
            self::WEEKLY => 'Weekly (7 days)',
            self::MONTHLY => 'Monthly (30 days)',
            self::QUARTERLY => 'Quarterly (90 days)',
            self::SEMI_ANNUAL => 'Semi-Annual (180 days)',
            self::ANNUAL => 'Annual (365 days)',
            self::REAL_TIME => 'Real-Time (Immediate)',
        };
    }
    
    /**
     * Calculate next screening date from given date
     */
    public function calculateNextScreeningDate(\DateTimeImmutable $fromDate): \DateTimeImmutable
    {
        if ($this === self::REAL_TIME) {
            return $fromDate; // Always now
        }
        
        return $fromDate->modify("+{$this->getDays()} days");
    }
}
