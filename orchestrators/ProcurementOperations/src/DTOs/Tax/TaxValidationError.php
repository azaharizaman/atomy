<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Tax;

/**
 * Represents a tax validation error.
 */
final readonly class TaxValidationError
{
    public function __construct(
        public string $code,
        public string $message,
        public string $field,
        public mixed $actualValue,
        public mixed $expectedValue = null,
        public string $severity = 'error',
    ) {}

    public static function invalidTaxCode(string $taxCode): self
    {
        return new self(
            code: 'INVALID_TAX_CODE',
            message: "Tax code '{$taxCode}' is not valid for this jurisdiction.",
            field: 'tax_code',
            actualValue: $taxCode,
        );
    }

    public static function rateMismatch(string $taxCode, float $actual, float $expected): self
    {
        return new self(
            code: 'TAX_RATE_MISMATCH',
            message: "Tax rate for '{$taxCode}' is {$actual}% but should be {$expected}%.",
            field: 'tax_rate',
            actualValue: $actual,
            expectedValue: $expected,
        );
    }

    public static function calculationMismatch(float $stated, float $calculated, float $variance): self
    {
        return new self(
            code: 'TAX_CALCULATION_MISMATCH',
            message: sprintf(
                'Tax amount mismatch: stated %.2f, calculated %.2f (variance: %.2f).',
                $stated,
                $calculated,
                $variance,
            ),
            field: 'tax_amount',
            actualValue: $stated,
            expectedValue: $calculated,
        );
    }

    public static function invalidVendorRegistration(string $registrationNumber): self
    {
        return new self(
            code: 'INVALID_VENDOR_TAX_REGISTRATION',
            message: "Vendor tax registration number '{$registrationNumber}' is invalid or expired.",
            field: 'vendor_tax_registration',
            actualValue: $registrationNumber,
        );
    }

    public static function missingReverseCharge(): self
    {
        return new self(
            code: 'MISSING_REVERSE_CHARGE',
            message: 'Reverse charge mechanism should be applied but was not.',
            field: 'reverse_charge',
            actualValue: false,
            expectedValue: true,
        );
    }

    public static function incorrectReverseCharge(): self
    {
        return new self(
            code: 'INCORRECT_REVERSE_CHARGE',
            message: 'Reverse charge mechanism was applied but should not be.',
            field: 'reverse_charge',
            actualValue: true,
            expectedValue: false,
        );
    }

    public static function missingTaxRegistration(): self
    {
        return new self(
            code: 'MISSING_TAX_REGISTRATION',
            message: 'Vendor tax registration number is required but not provided.',
            field: 'vendor_tax_registration',
            actualValue: null,
        );
    }

    public static function expiredExemption(string $vendorId): self
    {
        return new self(
            code: 'EXPIRED_TAX_EXEMPTION',
            message: "Tax exemption certificate for vendor '{$vendorId}' has expired.",
            field: 'tax_exemption',
            actualValue: 'expired',
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
            'actual_value' => $this->actualValue,
            'expected_value' => $this->expectedValue,
            'severity' => $this->severity,
        ];
    }
}
