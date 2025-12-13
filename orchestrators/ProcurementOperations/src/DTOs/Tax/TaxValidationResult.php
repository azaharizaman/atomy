<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;

/**
 * Result of invoice tax validation.
 */
final readonly class TaxValidationResult
{
    /**
     * @param array<TaxValidationError> $errors Validation errors
     * @param array<TaxValidationWarning> $warnings Validation warnings
     * @param array<string, mixed> $correctedValues Suggested corrections
     * @param array<string, mixed> $metadata Additional validation metadata
     */
    public function __construct(
        public string $invoiceId,
        public bool $isValid,
        public array $errors,
        public array $warnings,
        public ?Money $calculatedTax,
        public ?Money $statedTax,
        public ?Money $variance,
        public bool $reverseChargeApplicable,
        public bool $reverseChargeApplied,
        public bool $vendorTaxRegistrationValid,
        public array $correctedValues,
        public \DateTimeImmutable $validatedAt,
        public array $metadata = [],
    ) {}

    /**
     * Create a successful validation result.
     */
    public static function valid(
        string $invoiceId,
        Money $calculatedTax,
        Money $statedTax,
        bool $vendorTaxRegistrationValid = true,
        bool $reverseChargeApplicable = false,
        bool $reverseChargeApplied = false,
    ): self {
        return new self(
            invoiceId: $invoiceId,
            isValid: true,
            errors: [],
            warnings: [],
            calculatedTax: $calculatedTax,
            statedTax: $statedTax,
            variance: Money::of(
                $statedTax->getAmount() - $calculatedTax->getAmount(),
                $calculatedTax->getCurrency(),
            ),
            reverseChargeApplicable: $reverseChargeApplicable,
            reverseChargeApplied: $reverseChargeApplied,
            vendorTaxRegistrationValid: $vendorTaxRegistrationValid,
            correctedValues: [],
            validatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create an invalid validation result.
     *
     * @param array<TaxValidationError> $errors
     * @param array<TaxValidationWarning> $warnings
     * @param array<string, mixed> $correctedValues
     */
    public static function invalid(
        string $invoiceId,
        array $errors,
        ?Money $calculatedTax = null,
        ?Money $statedTax = null,
        array $warnings = [],
        array $correctedValues = [],
        bool $vendorTaxRegistrationValid = true,
    ): self {
        // Validate that both tax amounts are provided together or both are null
        if (($calculatedTax === null && $statedTax !== null) || ($calculatedTax !== null && $statedTax === null)) {
            throw new \InvalidArgumentException(
                'Both $calculatedTax and $statedTax must be provided together to compute variance, or both must be null.'
            );
        }

        $variance = null;
        if ($calculatedTax !== null && $statedTax !== null) {
            $variance = $statedTax->subtract($calculatedTax);
        }

        return new self(
            invoiceId: $invoiceId,
            isValid: false,
            errors: $errors,
            warnings: $warnings,
            calculatedTax: $calculatedTax,
            statedTax: $statedTax,
            variance: $variance,
            reverseChargeApplicable: false,
            reverseChargeApplied: false,
            vendorTaxRegistrationValid: $vendorTaxRegistrationValid,
            correctedValues: $correctedValues,
            validatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create result with warnings but still valid.
     *
     * @param array<TaxValidationWarning> $warnings
     */
    public static function validWithWarnings(
        string $invoiceId,
        Money $calculatedTax,
        Money $statedTax,
        array $warnings,
        bool $vendorTaxRegistrationValid = true,
    ): self {
        return new self(
            invoiceId: $invoiceId,
            isValid: true,
            errors: [],
            warnings: $warnings,
            calculatedTax: $calculatedTax,
            statedTax: $statedTax,
            variance: Money::of(
                $statedTax->getAmount() - $calculatedTax->getAmount(),
                $calculatedTax->getCurrency(),
            ),
            reverseChargeApplicable: false,
            reverseChargeApplied: false,
            vendorTaxRegistrationValid: $vendorTaxRegistrationValid,
            correctedValues: [],
            validatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Get error messages as strings.
     *
     * @return array<string>
     */
    public function getErrorMessages(): array
    {
        return array_map(
            fn(TaxValidationError $error) => $error->message,
            $this->errors,
        );
    }

    /**
     * Get warning messages as strings.
     *
     * @return array<string>
     */
    public function getWarningMessages(): array
    {
        return array_map(
            fn(TaxValidationWarning $warning) => $warning->message,
            $this->warnings,
        );
    }

    /**
     * Check if reverse charge was required but not applied.
     */
    public function hasMissingReverseCharge(): bool
    {
        return $this->reverseChargeApplicable && !$this->reverseChargeApplied;
    }

    /**
     * Get the variance as a percentage of stated tax.
     */
    public function getVariancePercentage(): ?float
    {
        if ($this->variance === null || $this->statedTax === null || $this->statedTax->isZero()) {
            return null;
        }

        return ($this->variance->getAmount() / $this->statedTax->getAmount()) * 100;
    }
}
