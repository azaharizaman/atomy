<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Tax;

/**
 * Represents a tax validation warning (non-blocking).
 */
final readonly class TaxValidationWarning
{
    public function __construct(
        public string $code,
        public string $message,
        public string $field,
        public mixed $value = null,
        public ?string $recommendation = null,
    ) {}

    public static function largeVariance(float $variancePercent): self
    {
        return new self(
            code: 'LARGE_TAX_VARIANCE',
            message: sprintf(
                'Tax variance of %.2f%% exceeds typical threshold. Review recommended.',
                $variancePercent,
            ),
            field: 'tax_amount',
            value: $variancePercent,
            recommendation: 'Review tax calculation with vendor.',
        );
    }

    public static function unusualTaxCode(string $taxCode, string $category): self
    {
        return new self(
            code: 'UNUSUAL_TAX_CODE',
            message: "Tax code '{$taxCode}' is unusual for category '{$category}'.",
            field: 'tax_code',
            value: $taxCode,
            recommendation: "Verify tax code is appropriate for {$category} purchases.",
        );
    }

    public static function exemptionExpiringSoon(string $vendorId, int $daysUntilExpiry): self
    {
        return new self(
            code: 'EXEMPTION_EXPIRING_SOON',
            message: "Tax exemption for vendor '{$vendorId}' expires in {$daysUntilExpiry} days.",
            field: 'tax_exemption',
            value: $daysUntilExpiry,
            recommendation: 'Request updated exemption certificate from vendor.',
        );
    }

    public static function highTaxRate(float $rate, float $standardRate): self
    {
        return new self(
            code: 'HIGH_TAX_RATE',
            message: sprintf(
                'Tax rate %.2f%% is higher than standard rate %.2f%%.',
                $rate,
                $standardRate,
            ),
            field: 'tax_rate',
            value: $rate,
            recommendation: 'Verify tax code and jurisdiction are correct.',
        );
    }

    public static function zeroTaxWithoutExemption(): self
    {
        return new self(
            code: 'ZERO_TAX_WITHOUT_EXEMPTION',
            message: 'Invoice has zero tax but no exemption reason provided.',
            field: 'tax_amount',
            value: 0.0,
            recommendation: 'Verify if exemption applies or if tax should be charged.',
        );
    }

    public static function crossBorderComplexity(string $vendorCountry, string $buyerCountry): self
    {
        return new self(
            code: 'CROSS_BORDER_COMPLEXITY',
            message: "Cross-border transaction ({$vendorCountry} → {$buyerCountry}) may have complex tax implications.",
            field: 'jurisdiction',
            value: "{$vendorCountry} → {$buyerCountry}",
            recommendation: 'Review with tax advisor for compliance.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'field' => $this->field,
            'value' => $this->value,
            'recommendation' => $this->recommendation,
        ];
    }
}
