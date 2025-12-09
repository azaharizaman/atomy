<?php

declare(strict_types=1);

namespace Nexus\Tax\Data\WithholdingRates;

use Nexus\Tax\Enums\PaymentType;

/**
 * US Withholding Tax Rates Data
 *
 * Reference: IRS Publication 515 (Withholding of Tax on Nonresident Aliens and Foreign Entities)
 *            IRS Publication 1281 (Backup Withholding)
 *
 * @see https://www.irs.gov/publications/p515
 * @see https://www.irs.gov/publications/p1281
 */
final class USWithholdingRates
{
    // ==================================================
    // Standard Rates (Chapter 3 - NRA Withholding)
    // ==================================================

    /**
     * Default rate for non-resident aliens (30%)
     * Applies when no treaty benefit claimed
     */
    public const float NRA_DEFAULT_RATE = 0.30;

    /**
     * Backup withholding rate (24%)
     * Applies to US persons who fail to provide valid TIN (W-9)
     */
    public const float BACKUP_WITHHOLDING_RATE = 0.24;

    /**
     * FATCA withholding rate (30%)
     * Applies to certain payments to foreign financial institutions
     * and non-financial foreign entities that don't comply with FATCA
     */
    public const float FATCA_WITHHOLDING_RATE = 0.30;

    // ==================================================
    // Payment Type Specific Rates (NRA - no treaty)
    // ==================================================

    /**
     * NRA rates by payment type (without treaty benefits)
     *
     * @var array<string, float>
     */
    public const array NRA_RATES_BY_PAYMENT_TYPE = [
        PaymentType::INTEREST->value => 0.30,
        PaymentType::DIVIDENDS->value => 0.30,
        PaymentType::ROYALTIES->value => 0.30,
        PaymentType::SERVICES->value => 0.30,
        PaymentType::PROFESSIONAL_SERVICES->value => 0.30,
        PaymentType::TECHNICAL_SERVICES->value => 0.30,
        PaymentType::MANAGEMENT_FEES->value => 0.30,
        PaymentType::RENT->value => 0.30,
        PaymentType::CONTRACT_PAYMENTS->value => 0.30,
    ];

    // ==================================================
    // Treaty Rates by Country
    // ==================================================

    /**
     * Reduced rates under US Tax Treaties
     *
     * Structure: [country_code => [payment_type => rate]]
     *
     * Note: This is a simplified subset. Full treaty rates require
     * careful analysis of specific treaty articles and limitations.
     * Rates shown are common reduced rates; actual treaty provisions
     * may have additional conditions or different rates for specific scenarios.
     *
     * @var array<string, array<string, float>>
     */
    public const array TREATY_RATES = [
        // United Kingdom (UK-US Treaty)
        'GB' => [
            PaymentType::INTEREST->value => 0.00,           // Art. 11 - generally 0%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. 10 - 15% (5% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.00,          // Art. 12 - generally 0%
            PaymentType::SERVICES->value => 0.00,           // Art. 14 - Business Profits exempt if no PE
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Canada (CA-US Treaty)
        'CA' => [
            PaymentType::INTEREST->value => 0.00,           // Art. XI - generally 0%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. X - 15% (5% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.00,          // Art. XII - generally 0%
            PaymentType::SERVICES->value => 0.00,           // Art. VII - Business Profits exempt if no PE
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Australia (AU-US Treaty)
        'AU' => [
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. 10 - 15% (5% for 80%+ ownership)
            PaymentType::ROYALTIES->value => 0.05,          // Art. 12 - 5%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Germany (DE-US Treaty)
        'DE' => [
            PaymentType::INTEREST->value => 0.00,           // Art. 11 - generally 0%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. 10 - 15% (5% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.00,          // Art. 12 - generally 0%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Japan (JP-US Treaty)
        'JP' => [
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::DIVIDENDS->value => 0.10,          // Art. 10 - 10% (5% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.00,          // Art. 12 - generally 0%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Singapore (SG-US Treaty)
        'SG' => [
            PaymentType::INTEREST->value => 0.00,           // Art. 11 - generally 0%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. 10 - 15% (5% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.00,          // Art. 12 - generally 0%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Malaysia (MY-US Treaty)
        'MY' => [
            PaymentType::INTEREST->value => 0.15,           // Art. 11 - 15%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. 10 - 15%
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::SERVICES->value => 0.00,           // Art. 7 - Business Profits
            PaymentType::TECHNICAL_SERVICES->value => 0.00, // Art. 13 - Technical Services
        ],

        // India (IN-US Treaty)
        'IN' => [
            PaymentType::INTEREST->value => 0.15,           // Art. 11 - 15%
            PaymentType::DIVIDENDS->value => 0.25,          // Art. 10 - 25% (15% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.15,          // Art. 12 - 15%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::TECHNICAL_SERVICES->value => 0.15, // Art. 12 - FTS 15%
        ],

        // China (CN-US Treaty)
        'CN' => [
            PaymentType::INTEREST->value => 0.10,           // Art. 10 - 10%
            PaymentType::DIVIDENDS->value => 0.10,          // Art. 9 - 10%
            PaymentType::ROYALTIES->value => 0.10,          // Art. 11 - 10%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Netherlands (NL-US Treaty)
        'NL' => [
            PaymentType::INTEREST->value => 0.00,           // Art. 12 - generally 0%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. 10 - 15% (5% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.00,          // Art. 13 - generally 0%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],

        // Ireland (IE-US Treaty)
        'IE' => [
            PaymentType::INTEREST->value => 0.00,           // Art. 11 - generally 0%
            PaymentType::DIVIDENDS->value => 0.15,          // Art. 10 - 15% (5% for 10%+ ownership)
            PaymentType::ROYALTIES->value => 0.00,          // Art. 12 - generally 0%
            PaymentType::SERVICES->value => 0.00,
            PaymentType::PROFESSIONAL_SERVICES->value => 0.00,
        ],
    ];

    // ==================================================
    // Form Requirements
    // ==================================================

    /**
     * US Form requirements by scenario
     */
    public const array FORM_REQUIREMENTS = [
        'domestic_backup' => [
            'form' => '1099',
            'description' => 'Information return for backup withholding',
            'threshold_cents' => 60000, // $600 threshold for most 1099 forms
        ],
        'nra_withholding' => [
            'form' => '1042-S',
            'description' => 'Foreign Person\'s U.S. Source Income Subject to Withholding',
            'threshold_cents' => 0, // No minimum threshold
        ],
        'fatca_withholding' => [
            'form' => '1042-S',
            'description' => 'FATCA withholding reported on 1042-S',
            'threshold_cents' => 0,
        ],
    ];

    // ==================================================
    // Helper Methods
    // ==================================================

    /**
     * Get treaty rate for specific country and payment type
     */
    public static function getTreatyRate(string $countryCode, PaymentType $paymentType): ?float
    {
        $country = strtoupper($countryCode);
        $type = $paymentType->value;

        return self::TREATY_RATES[$country][$type] ?? null;
    }

    /**
     * Check if a treaty exists with the specified country
     */
    public static function hasTreaty(string $countryCode): bool
    {
        return isset(self::TREATY_RATES[strtoupper($countryCode)]);
    }

    /**
     * Get all treaty countries
     *
     * @return array<string>
     */
    public static function getTreatyCountries(): array
    {
        return array_keys(self::TREATY_RATES);
    }

    /**
     * Get NRA rate for payment type (non-treaty)
     */
    public static function getNraRate(PaymentType $paymentType): float
    {
        return self::NRA_RATES_BY_PAYMENT_TYPE[$paymentType->value] ?? self::NRA_DEFAULT_RATE;
    }

    /**
     * Get effective rate considering treaty benefits
     */
    public static function getEffectiveRate(
        PaymentType $paymentType,
        bool $isResident,
        bool $hasValidTin,
        ?string $treatyCountry = null
    ): float {
        // US resident with valid TIN - no withholding
        if ($isResident && $hasValidTin) {
            return 0.0;
        }

        // US resident without valid TIN - backup withholding
        if ($isResident && !$hasValidTin) {
            return self::BACKUP_WITHHOLDING_RATE;
        }

        // Non-resident with treaty benefits
        if (!$isResident && $treatyCountry !== null && self::hasTreaty($treatyCountry)) {
            $treatyRate = self::getTreatyRate($treatyCountry, $paymentType);
            if ($treatyRate !== null) {
                return $treatyRate;
            }
        }

        // Non-resident without treaty - standard NRA rate
        return self::getNraRate($paymentType);
    }
}
