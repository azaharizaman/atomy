<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Enums;

/**
 * Geographic/Jurisdiction AML Risk Classification
 * 
 * Based on FATF high-risk jurisdictions, EU AML directives,
 * and international sanctions lists.
 */
enum JurisdictionRisk: string
{
    /**
     * Low risk jurisdictions
     * Strong AML frameworks, FATF compliant
     */
    case LOW = 'low';

    /**
     * Medium risk jurisdictions
     * Adequate AML frameworks but some deficiencies
     */
    case MEDIUM = 'medium';

    /**
     * High risk jurisdictions
     * Strategic AML deficiencies, FATF grey list
     */
    case HIGH = 'high';

    /**
     * Very high risk jurisdictions
     * FATF blacklist, severe sanctions
     */
    case VERY_HIGH = 'very_high';

    /**
     * Get risk weight for scoring (0.0 - 1.0)
     */
    public function getWeight(): float
    {
        return match ($this) {
            self::LOW => 0.1,
            self::MEDIUM => 0.4,
            self::HIGH => 0.7,
            self::VERY_HIGH => 1.0,
        };
    }

    /**
     * Get the score contribution (0-100)
     */
    public function getScoreContribution(): int
    {
        return match ($this) {
            self::LOW => 10,
            self::MEDIUM => 40,
            self::HIGH => 70,
            self::VERY_HIGH => 100,
        };
    }

    /**
     * Get FATF-listed high-risk countries (grey list)
     * Note: This list should be updated periodically based on FATF publications
     * 
     * @return array<string> ISO 3166-1 alpha-2 country codes
     */
    public static function getFatfGreyListCountries(): array
    {
        // FATF grey list as of 2024 (Jurisdictions under Increased Monitoring)
        return [
            'BG', // Bulgaria
            'BF', // Burkina Faso
            'CM', // Cameroon
            'CD', // Democratic Republic of Congo
            'HR', // Croatia
            'HT', // Haiti
            'KE', // Kenya
            'ML', // Mali
            'MZ', // Mozambique
            'NG', // Nigeria
            'PH', // Philippines
            'SN', // Senegal
            'ZA', // South Africa
            'SS', // South Sudan
            'SY', // Syria
            'TZ', // Tanzania
            'VN', // Vietnam
            'YE', // Yemen
        ];
    }

    /**
     * Get FATF-listed blacklist countries
     * Note: This list should be updated periodically based on FATF publications
     * 
     * @return array<string> ISO 3166-1 alpha-2 country codes
     */
    public static function getFatfBlackListCountries(): array
    {
        // FATF blacklist (Call for Action jurisdictions)
        return [
            'KP', // North Korea
            'IR', // Iran
            'MM', // Myanmar
        ];
    }

    /**
     * Get EU high-risk third countries
     * 
     * @return array<string> ISO 3166-1 alpha-2 country codes
     */
    public static function getEuHighRiskCountries(): array
    {
        return [
            'AF', // Afghanistan
            'BS', // Bahamas
            'BB', // Barbados
            'BW', // Botswana
            'KH', // Cambodia
            'GH', // Ghana
            'JM', // Jamaica
            'MU', // Mauritius
            'NI', // Nicaragua
            'PK', // Pakistan
            'PA', // Panama
            'TT', // Trinidad and Tobago
            'UG', // Uganda
            'VU', // Vanuatu
            'ZW', // Zimbabwe
        ];
    }

    /**
     * Get low-risk jurisdictions (strong AML frameworks)
     * 
     * @return array<string> ISO 3166-1 alpha-2 country codes
     */
    public static function getLowRiskCountries(): array
    {
        return [
            'AU', // Australia
            'AT', // Austria
            'BE', // Belgium
            'CA', // Canada
            'DK', // Denmark
            'FI', // Finland
            'FR', // France
            'DE', // Germany
            'HK', // Hong Kong
            'IE', // Ireland
            'JP', // Japan
            'LU', // Luxembourg
            'NL', // Netherlands
            'NZ', // New Zealand
            'NO', // Norway
            'SG', // Singapore
            'SE', // Sweden
            'CH', // Switzerland
            'GB', // United Kingdom
            'US', // United States
        ];
    }

    /**
     * Determine jurisdiction risk from ISO 3166-1 alpha-2 country code
     */
    public static function fromCountryCode(string $countryCode): self
    {
        $code = strtoupper($countryCode);

        if (in_array($code, self::getFatfBlackListCountries(), true)) {
            return self::VERY_HIGH;
        }

        if (in_array($code, self::getFatfGreyListCountries(), true)) {
            return self::HIGH;
        }

        if (in_array($code, self::getEuHighRiskCountries(), true)) {
            return self::HIGH;
        }

        if (in_array($code, self::getLowRiskCountries(), true)) {
            return self::LOW;
        }

        // Default to medium for unlisted countries
        return self::MEDIUM;
    }

    /**
     * Check if enhanced due diligence is required
     */
    public function requiresEdd(): bool
    {
        return match ($this) {
            self::LOW => false,
            self::MEDIUM => false,
            self::HIGH => true,
            self::VERY_HIGH => true,
        };
    }

    /**
     * Check if business relationship is prohibited
     */
    public function isProhibited(): bool
    {
        return $this === self::VERY_HIGH;
    }

    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::LOW => 'Low risk - Strong AML framework, FATF compliant',
            self::MEDIUM => 'Medium risk - Adequate AML framework with some deficiencies',
            self::HIGH => 'High risk - FATF grey list or EU high-risk country',
            self::VERY_HIGH => 'Very high risk - FATF blacklist, business relationship may be prohibited',
        };
    }

    /**
     * Get the minimum transaction review threshold
     */
    public function getTransactionReviewThreshold(): float
    {
        return match ($this) {
            self::LOW => 50000.00,      // Review transactions above 50k
            self::MEDIUM => 25000.00,    // Review transactions above 25k
            self::HIGH => 10000.00,      // Review transactions above 10k
            self::VERY_HIGH => 1000.00,  // Review almost all transactions
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
     * Get the reporting requirement level
     */
    public function getReportingLevel(): string
    {
        return match ($this) {
            self::LOW => 'standard',
            self::MEDIUM => 'enhanced',
            self::HIGH => 'intensive',
            self::VERY_HIGH => 'immediate',
        };
    }

    /**
     * Get all risk levels in ascending order
     * 
     * @return array<self>
     */
    public static function ascending(): array
    {
        return [self::LOW, self::MEDIUM, self::HIGH, self::VERY_HIGH];
    }
}
