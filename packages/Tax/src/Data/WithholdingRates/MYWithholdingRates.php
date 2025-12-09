<?php

declare(strict_types=1);

namespace Nexus\Tax\Data\WithholdingRates;

use Nexus\Tax\Enums\PaymentType;

/**
 * Malaysian Withholding Tax Rates Data
 *
 * Reference: Income Tax Act 1967 (ITA 1967)
 *            - Section 109: Interest and royalty to non-residents
 *            - Section 109B: Technical/management fees to non-residents
 *            - Section 107A: Contract payments to non-resident contractors
 *
 * @see https://www.hasil.gov.my/
 */
final class MYWithholdingRates
{
    // ==================================================
    // Standard Withholding Rates (Non-Resident)
    // ==================================================

    /**
     * Section 109: Royalty payments to non-residents
     * Effective from YA 2024
     */
    public const float ROYALTY_RATE = 0.10;

    /**
     * Section 109: Interest payments to non-residents
     */
    public const float INTEREST_RATE = 0.15;

    /**
     * Section 109B: Technical fees to non-residents
     * (Technical, management, or consultancy services)
     */
    public const float TECHNICAL_FEE_RATE = 0.10;

    /**
     * Section 109B: Management fees to non-residents
     */
    public const float MANAGEMENT_FEE_RATE = 0.10;

    /**
     * Section 107A: Contract payments to non-resident contractors
     * For contracts exceeding RM10,000
     */
    public const float CONTRACT_PAYMENT_RATE = 0.10;

    /**
     * Section 107A: Contract retention rate for services portion
     * Additional withholding on service component of contracts
     */
    public const float CONTRACT_SERVICE_RETENTION_RATE = 0.03;

    /**
     * Section 109E: Public entertainer payments
     * Payments to non-resident public entertainers
     */
    public const float PUBLIC_ENTERTAINER_RATE = 0.15;

    /**
     * Section 4A: Special classes of income
     * (Installation fees, equipment rental, etc.)
     */
    public const float SPECIAL_INCOME_RATE = 0.10;

    // ==================================================
    // Rates by Payment Type
    // ==================================================

    /**
     * WHT rates by payment type for non-residents
     *
     * @var array<string, float>
     */
    public const array RATES_BY_PAYMENT_TYPE = [
        PaymentType::ROYALTIES->value => self::ROYALTY_RATE,
        PaymentType::INTEREST->value => self::INTEREST_RATE,
        PaymentType::TECHNICAL_SERVICES->value => self::TECHNICAL_FEE_RATE,
        PaymentType::MANAGEMENT_FEES->value => self::MANAGEMENT_FEE_RATE,
        PaymentType::PROFESSIONAL_SERVICES->value => self::TECHNICAL_FEE_RATE,
        PaymentType::CONTRACT_PAYMENTS->value => self::CONTRACT_PAYMENT_RATE,
        PaymentType::SERVICES->value => self::TECHNICAL_FEE_RATE,
        PaymentType::COMMISSIONS->value => self::TECHNICAL_FEE_RATE,
    ];

    /**
     * ITA Section references by payment type
     *
     * @var array<string, string>
     */
    public const array SECTION_REFERENCES = [
        PaymentType::ROYALTIES->value => 'S.109',
        PaymentType::INTEREST->value => 'S.109',
        PaymentType::TECHNICAL_SERVICES->value => 'S.109B',
        PaymentType::MANAGEMENT_FEES->value => 'S.109B',
        PaymentType::PROFESSIONAL_SERVICES->value => 'S.109B',
        PaymentType::CONTRACT_PAYMENTS->value => 'S.107A',
        PaymentType::SERVICES->value => 'S.109B',
        PaymentType::COMMISSIONS->value => 'S.109B',
    ];

    // ==================================================
    // Treaty Rates
    // ==================================================

    /**
     * Reduced rates under Malaysia's Double Tax Agreements (DTAs)
     *
     * Structure: [country_code => [payment_type => rate]]
     *
     * Note: Malaysia has DTAs with 75+ countries. This is a subset of
     * common trading partners. Full treaty analysis required for
     * production use.
     *
     * @var array<string, array<string, float>>
     */
    public const array TREATY_RATES = [
        // Singapore (MY-SG DTA)
        'SG' => [
            PaymentType::ROYALTIES->value => 0.08,          // Art. 12 - 8%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.05, // Art. 12A - 5%
            PaymentType::MANAGEMENT_FEES->value => 0.05,
        ],

        // United Kingdom (MY-UK DTA)
        'GB' => [
            PaymentType::ROYALTIES->value => 0.08,          // Art. 12 - 8%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.08, // Art. 12
            PaymentType::MANAGEMENT_FEES->value => 0.08,
        ],

        // Australia (MY-AU DTA)
        'AU' => [
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::INTEREST->value => 0.15,           // Art. 11 - 15%
            PaymentType::TECHNICAL_SERVICES->value => 0.10,
            PaymentType::MANAGEMENT_FEES->value => 0.10,
        ],

        // United States (MY-US DTA)
        'US' => [
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::INTEREST->value => 0.15,           // Art. 11 - 15%
            PaymentType::TECHNICAL_SERVICES->value => 0.00, // Art. 13 - may be exempt
            PaymentType::MANAGEMENT_FEES->value => 0.00,
        ],

        // Japan (MY-JP DTA)
        'JP' => [
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.10,
            PaymentType::MANAGEMENT_FEES->value => 0.10,
        ],

        // China (MY-CN DTA)
        'CN' => [
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.10,
            PaymentType::MANAGEMENT_FEES->value => 0.10,
        ],

        // India (MY-IN DTA)
        'IN' => [
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.10, // Art. 12 - FTS
            PaymentType::MANAGEMENT_FEES->value => 0.10,
        ],

        // Hong Kong (MY-HK DTA)
        'HK' => [
            PaymentType::ROYALTIES->value => 0.08,          // Art. 12 - 8%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.05, // Art. 12A - 5%
            PaymentType::MANAGEMENT_FEES->value => 0.05,
        ],

        // Indonesia (MY-ID DTA)
        'ID' => [
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.10,
            PaymentType::MANAGEMENT_FEES->value => 0.10,
        ],

        // Thailand (MY-TH DTA)
        'TH' => [
            PaymentType::ROYALTIES->value => 0.10,          // Art. 12 - 10%
            PaymentType::INTEREST->value => 0.15,           // Art. 11 - 15%
            PaymentType::TECHNICAL_SERVICES->value => 0.10,
            PaymentType::MANAGEMENT_FEES->value => 0.10,
        ],

        // Netherlands (MY-NL DTA)
        'NL' => [
            PaymentType::ROYALTIES->value => 0.08,          // Art. 12 - 8%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.05,
            PaymentType::MANAGEMENT_FEES->value => 0.05,
        ],

        // Germany (MY-DE DTA)
        'DE' => [
            PaymentType::ROYALTIES->value => 0.07,          // Art. 12 - 7%
            PaymentType::INTEREST->value => 0.10,           // Art. 11 - 10%
            PaymentType::TECHNICAL_SERVICES->value => 0.07,
            PaymentType::MANAGEMENT_FEES->value => 0.07,
        ],
    ];

    // ==================================================
    // Form Requirements (CP37 Series)
    // ==================================================

    /**
     * CP37 form variants by payment type
     *
     * @var array<string, array<string, string>>
     */
    public const array FORM_REQUIREMENTS = [
        PaymentType::ROYALTIES->value => [
            'form' => 'CP37',
            'description' => 'Notification of payment to non-resident - Royalty',
            'section' => '109',
        ],
        PaymentType::INTEREST->value => [
            'form' => 'CP37',
            'description' => 'Notification of payment to non-resident - Interest',
            'section' => '109',
        ],
        PaymentType::TECHNICAL_SERVICES->value => [
            'form' => 'CP37A',
            'description' => 'Notification of payment to non-resident - Technical Fees',
            'section' => '109B',
        ],
        PaymentType::MANAGEMENT_FEES->value => [
            'form' => 'CP37A',
            'description' => 'Notification of payment to non-resident - Management Fees',
            'section' => '109B',
        ],
        PaymentType::CONTRACT_PAYMENTS->value => [
            'form' => 'CP37D',
            'description' => 'Notification of payment to non-resident contractor',
            'section' => '107A',
        ],
        PaymentType::PROFESSIONAL_SERVICES->value => [
            'form' => 'CP37A',
            'description' => 'Notification of payment to non-resident - Professional Fees',
            'section' => '109B',
        ],
    ];

    /**
     * Thresholds for withholding requirements (in cents)
     */
    public const array THRESHOLDS = [
        // Section 107A: Contract payments threshold RM10,000
        'contract_threshold_cents' => 1000000,

        // No minimum threshold for other WHT payments
        'default_threshold_cents' => 0,
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
     * Check if a DTA exists with the specified country
     */
    public static function hasTreaty(string $countryCode): bool
    {
        return isset(self::TREATY_RATES[strtoupper($countryCode)]);
    }

    /**
     * Get all DTA countries
     *
     * @return array<string>
     */
    public static function getTreatyCountries(): array
    {
        return array_keys(self::TREATY_RATES);
    }

    /**
     * Get domestic WHT rate for payment type
     */
    public static function getDomesticRate(PaymentType $paymentType): float
    {
        return self::RATES_BY_PAYMENT_TYPE[$paymentType->value] ?? self::TECHNICAL_FEE_RATE;
    }

    /**
     * Get ITA section reference for payment type
     */
    public static function getSectionReference(PaymentType $paymentType): string
    {
        return self::SECTION_REFERENCES[$paymentType->value] ?? 'S.109B';
    }

    /**
     * Get effective rate considering DTA benefits
     */
    public static function getEffectiveRate(
        PaymentType $paymentType,
        bool $isResident,
        ?string $treatyCountry = null
    ): float {
        // Malaysian residents - no WHT
        if ($isResident) {
            return 0.0;
        }

        // Non-resident with DTA benefits
        if ($treatyCountry !== null && self::hasTreaty($treatyCountry)) {
            $treatyRate = self::getTreatyRate($treatyCountry, $paymentType);
            if ($treatyRate !== null) {
                return $treatyRate;
            }
        }

        // Non-resident without DTA - domestic rate
        return self::getDomesticRate($paymentType);
    }

    /**
     * Get required form for payment type
     *
     * @return array<string, string>|null
     */
    public static function getFormRequirement(PaymentType $paymentType): ?array
    {
        return self::FORM_REQUIREMENTS[$paymentType->value] ?? null;
    }

    /**
     * Determine CP37 form variant for payment type
     */
    public static function getFormType(PaymentType $paymentType): string
    {
        return self::FORM_REQUIREMENTS[$paymentType->value]['form'] ?? 'CP37A';
    }

    /**
     * Check if contract payment exceeds threshold
     */
    public static function exceedsContractThreshold(int $amountCents): bool
    {
        return $amountCents >= self::THRESHOLDS['contract_threshold_cents'];
    }
}
