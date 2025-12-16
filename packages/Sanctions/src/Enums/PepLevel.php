<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Enums;

/**
 * PEP (Politically Exposed Person) risk level classification
 * 
 * Based on FATF guidance and international AML standards
 */
enum PepLevel: string
{
    /**
     * High-level PEP (Direct political exposure)
     * - Heads of state or government
     * - Senior politicians and government officials
     * - Senior judicial or military officials
     * - Senior executives of state-owned corporations
     * - Important political party officials
     * - Highest AML risk - Enhanced Due Diligence required
     */
    case HIGH = 'high';
    
    /**
     * Medium-level PEP (Indirect or lower political exposure)
     * - Mid-level government officials
     * - Lower-ranking military officers
     * - Mid-level judicial officials
     * - Regional political figures
     * - Moderate AML risk - Standard Enhanced Due Diligence
     */
    case MEDIUM = 'medium';
    
    /**
     * Low-level PEP (Minimal political exposure)
     * - Former PEPs (>12 months out of office)
     * - Lower administrative positions
     * - Honorary positions with limited influence
     * - Low AML risk - Standard Due Diligence may suffice
     */
    case LOW = 'low';
    
    /**
     * Not a PEP
     * - No political exposure
     * - Standard CDD (Customer Due Diligence) applies
     */
    case NONE = 'none';
    
    /**
     * Get Enhanced Due Diligence (EDD) requirements
     */
    public function getEddRequirements(): array
    {
        return match ($this) {
            self::HIGH => [
                'senior_management_approval' => true,
                'source_of_wealth_verification' => true,
                'source_of_funds_verification' => true,
                'ongoing_monitoring_frequency' => 'monthly',
                'transaction_monitoring' => 'enhanced',
                'adverse_media_screening' => true,
                'relationship_justification' => true,
            ],
            self::MEDIUM => [
                'senior_management_approval' => true,
                'source_of_wealth_verification' => true,
                'source_of_funds_verification' => false,
                'ongoing_monitoring_frequency' => 'quarterly',
                'transaction_monitoring' => 'standard',
                'adverse_media_screening' => true,
                'relationship_justification' => false,
            ],
            self::LOW => [
                'senior_management_approval' => false,
                'source_of_wealth_verification' => false,
                'source_of_funds_verification' => false,
                'ongoing_monitoring_frequency' => 'annually',
                'transaction_monitoring' => 'standard',
                'adverse_media_screening' => false,
                'relationship_justification' => false,
            ],
            self::NONE => [
                'senior_management_approval' => false,
                'source_of_wealth_verification' => false,
                'source_of_funds_verification' => false,
                'ongoing_monitoring_frequency' => 'annually',
                'transaction_monitoring' => 'standard',
                'adverse_media_screening' => false,
                'relationship_justification' => false,
            ],
        };
    }
    
    /**
     * Get risk score (0-100)
     */
    public function getRiskScore(): int
    {
        return match ($this) {
            self::HIGH => 90,
            self::MEDIUM => 60,
            self::LOW => 30,
            self::NONE => 0,
        };
    }
    
    /**
     * Check if Enhanced Due Diligence is required
     */
    public function requiresEdd(): bool
    {
        return match ($this) {
            self::HIGH, self::MEDIUM => true,
            default => false,
        };
    }
    
    /**
     * Check if senior management approval is required
     */
    public function requiresSeniorApproval(): bool
    {
        return match ($this) {
            self::HIGH, self::MEDIUM => true,
            default => false,
        };
    }
    
    /**
     * Get monitoring frequency in days
     */
    public function getMonitoringFrequencyDays(): int
    {
        return match ($this) {
            self::HIGH => 30,      // Monthly
            self::MEDIUM => 90,    // Quarterly
            self::LOW => 365,      // Annually
            self::NONE => 365,     // Annually
        };
    }
}
