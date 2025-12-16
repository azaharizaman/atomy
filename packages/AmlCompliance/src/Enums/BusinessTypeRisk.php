<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Enums;

/**
 * Business Type AML Risk Classification
 * 
 * Based on FATF guidelines on high-risk business sectors
 * and money laundering vulnerabilities.
 */
enum BusinessTypeRisk: string
{
    /**
     * Low risk business types
     * Standard retail, manufacturing, professional services
     */
    case LOW = 'low';

    /**
     * Medium risk business types
     * Real estate, precious metals, used vehicles
     */
    case MEDIUM = 'medium';

    /**
     * High risk business types
     * MSBs, crypto, gambling, arms trade, cash-intensive
     */
    case HIGH = 'high';

    /**
     * Get risk weight for scoring (0.0 - 1.0)
     */
    public function getWeight(): float
    {
        return match ($this) {
            self::LOW => 0.1,
            self::MEDIUM => 0.4,
            self::HIGH => 0.8,
        };
    }

    /**
     * Get the score contribution (0-100)
     */
    public function getScoreContribution(): int
    {
        return match ($this) {
            self::LOW => 10,
            self::MEDIUM => 50,
            self::HIGH => 100,
        };
    }

    /**
     * Get high-risk industry codes (NAICS-based categories)
     * 
     * @return array<string, string> Code => Description
     */
    public static function getHighRiskIndustries(): array
    {
        return [
            'MSB' => 'Money Services Business',
            'CRYPTO' => 'Cryptocurrency Exchange/Services',
            'GAMBLING' => 'Gambling and Gaming',
            'CASINO' => 'Casino and Card Rooms',
            'ARMS' => 'Arms and Defense Trade',
            'ART' => 'Art Dealers and Auction Houses',
            'PRECIOUS_METALS' => 'Precious Metals and Stones Dealers',
            'ADULT' => 'Adult Entertainment',
            'MARIJUANA' => 'Cannabis/Marijuana Business',
            'SHELL' => 'Shell Company/Company Formation',
            'TRUST' => 'Trust and Company Service Providers',
            'ATM' => 'ATM Operators',
            'FOREX' => 'Foreign Exchange Services',
            'REMITTANCE' => 'Money Remittance Services',
            'PAWNSHOP' => 'Pawnshops',
            'CHECK_CASHING' => 'Check Cashing Services',
            'PREPAID' => 'Prepaid Cards and Stored Value',
        ];
    }

    /**
     * Get medium-risk industry codes
     * 
     * @return array<string, string> Code => Description
     */
    public static function getMediumRiskIndustries(): array
    {
        return [
            'REAL_ESTATE' => 'Real Estate Development/Sales',
            'CONSTRUCTION' => 'Construction and Building',
            'JEWELRY' => 'Jewelry Retail',
            'USED_CARS' => 'Used Vehicle Dealers',
            'IMPORT_EXPORT' => 'Import/Export Trading',
            'HOSPITALITY' => 'Hotels, Restaurants, Nightclubs',
            'LAWYER' => 'Legal Services (non-escrow)',
            'ACCOUNTANT' => 'Accounting Services',
            'CAR_WASH' => 'Car Wash Services',
            'LAUNDROMAT' => 'Laundromats',
            'CONVENIENCE' => 'Convenience Stores',
            'PARKING' => 'Parking Services',
            'ELECTRONICS' => 'Electronics and Mobile Phone Dealers',
            'NONPROFIT' => 'Non-Profit Organizations',
            'CHARITY' => 'Charitable Organizations',
        ];
    }

    /**
     * Get low-risk industry codes
     * 
     * @return array<string, string> Code => Description
     */
    public static function getLowRiskIndustries(): array
    {
        return [
            'RETAIL' => 'General Retail',
            'MANUFACTURING' => 'Manufacturing',
            'TECHNOLOGY' => 'Technology and Software',
            'HEALTHCARE' => 'Healthcare Services',
            'EDUCATION' => 'Educational Institutions',
            'GOVERNMENT' => 'Government Agencies',
            'BANK' => 'Regulated Banks and Credit Unions',
            'INSURANCE' => 'Regulated Insurance Companies',
            'UTILITIES' => 'Utilities and Infrastructure',
            'TELECOM' => 'Telecommunications',
            'TRANSPORTATION' => 'Transportation Services',
            'AGRICULTURE' => 'Agriculture and Farming',
            'FOOD_SERVICE' => 'Restaurants and Food Service (chain)',
            'PROFESSIONAL' => 'Professional Services',
            'MEDIA' => 'Media and Publishing',
        ];
    }

    /**
     * Determine business type risk from industry code
     */
    public static function fromIndustryCode(string $industryCode): self
    {
        $code = strtoupper($industryCode);

        if (array_key_exists($code, self::getHighRiskIndustries())) {
            return self::HIGH;
        }

        if (array_key_exists($code, self::getMediumRiskIndustries())) {
            return self::MEDIUM;
        }

        if (array_key_exists($code, self::getLowRiskIndustries())) {
            return self::LOW;
        }

        // Default to medium for unknown industries
        return self::MEDIUM;
    }

    /**
     * Determine business type risk from NAICS code
     * 
     * @param string $naicsCode 6-digit NAICS code
     */
    public static function fromNaicsCode(string $naicsCode): self
    {
        // Get the first 2 digits for sector identification
        $sector = substr($naicsCode, 0, 2);

        // High-risk NAICS sectors
        $highRiskSectors = [
            '71' => 'Arts, Entertainment, Recreation (includes gambling)',
        ];

        // Medium-risk NAICS sectors
        $mediumRiskSectors = [
            '23' => 'Construction',
            '53' => 'Real Estate',
            '44' => 'Retail Trade (partial)',
            '72' => 'Accommodation and Food Services',
        ];

        // High-risk specific 4-digit codes
        $highRiskCodes = [
            '7132' => 'Gambling Industries',
            '5223' => 'Activities Related to Credit Intermediation',
            '5231' => 'Securities and Commodity Exchanges',
        ];

        $prefix4 = substr($naicsCode, 0, 4);

        if (array_key_exists($prefix4, $highRiskCodes)) {
            return self::HIGH;
        }

        if (array_key_exists($sector, $highRiskSectors)) {
            return self::HIGH;
        }

        if (array_key_exists($sector, $mediumRiskSectors)) {
            return self::MEDIUM;
        }

        return self::LOW;
    }

    /**
     * Check if enhanced due diligence is required
     */
    public function requiresEdd(): bool
    {
        return $this === self::HIGH;
    }

    /**
     * Check if business type is cash-intensive
     */
    public function isCashIntensive(): bool
    {
        return match ($this) {
            self::LOW => false,
            self::MEDIUM => true, // Many medium-risk are cash-intensive
            self::HIGH => true,
        };
    }

    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::LOW => 'Low risk - Standard retail, manufacturing, professional services',
            self::MEDIUM => 'Medium risk - Real estate, precious metals, cash-intensive businesses',
            self::HIGH => 'High risk - MSBs, crypto, gambling, arms trade',
        };
    }

    /**
     * Get transaction monitoring intensity level
     */
    public function getMonitoringIntensity(): string
    {
        return match ($this) {
            self::LOW => 'standard',
            self::MEDIUM => 'elevated',
            self::HIGH => 'intensive',
        };
    }

    /**
     * Get the transaction structuring threshold to watch for
     */
    public function getStructuringThreshold(): float
    {
        return match ($this) {
            self::LOW => 10000.00,  // Standard CTR threshold
            self::MEDIUM => 8000.00, // Slightly below CTR
            self::HIGH => 5000.00,   // Much lower for high-risk
        };
    }

    /**
     * Check if this risk level is higher than another
     */
    public function isHigherThan(self $other): bool
    {
        return $this->getWeight() > $other->getWeight();
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
