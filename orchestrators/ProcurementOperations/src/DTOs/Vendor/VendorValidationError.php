<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

/**
 * Vendor validation error DTO.
 *
 * Represents a validation error encountered during vendor onboarding or update.
 */
final readonly class VendorValidationError
{
    /**
     * @param string $field Field that failed validation
     * @param string $code Error code
     * @param string $message Human-readable error message
     * @param string $severity Error severity (error, warning, info)
     * @param bool $isBlocking Whether this blocks the operation
     * @param mixed $rejectedValue The value that was rejected
     * @param string|null $suggestion Suggested fix
     */
    public function __construct(
        public string $field,
        public string $code,
        public string $message,
        public string $severity = 'error',
        public bool $isBlocking = true,
        public mixed $rejectedValue = null,
        public ?string $suggestion = null,
    ) {}

    public static function required(string $field, string $fieldLabel): self
    {
        return new self(
            field: $field,
            code: 'REQUIRED',
            message: "{$fieldLabel} is required",
            severity: 'error',
            isBlocking: true,
            rejectedValue: null,
            suggestion: "Please provide a value for {$fieldLabel}",
        );
    }

    public static function invalidFormat(string $field, string $fieldLabel, string $expectedFormat, mixed $actualValue): self
    {
        return new self(
            field: $field,
            code: 'INVALID_FORMAT',
            message: "{$fieldLabel} has invalid format. Expected: {$expectedFormat}",
            severity: 'error',
            isBlocking: true,
            rejectedValue: $actualValue,
            suggestion: "Please use the format: {$expectedFormat}",
        );
    }

    public static function duplicate(string $field, string $fieldLabel, mixed $duplicateValue): self
    {
        return new self(
            field: $field,
            code: 'DUPLICATE',
            message: "{$fieldLabel} already exists in the system",
            severity: 'error',
            isBlocking: true,
            rejectedValue: $duplicateValue,
            suggestion: 'Please use a unique value or contact support if this is the same vendor',
        );
    }

    public static function invalidCountry(string $countryCode): self
    {
        return new self(
            field: 'country_code',
            code: 'INVALID_COUNTRY',
            message: "Country code '{$countryCode}' is not supported",
            severity: 'error',
            isBlocking: true,
            rejectedValue: $countryCode,
            suggestion: 'Please use a valid ISO 3166-1 alpha-2 country code',
        );
    }

    public static function sanctionedEntity(string $field, string $sanctionList): self
    {
        return new self(
            field: $field,
            code: 'SANCTIONED_ENTITY',
            message: "Entity matches a sanctioned entity on {$sanctionList}",
            severity: 'error',
            isBlocking: true,
            rejectedValue: null,
            suggestion: 'Cannot proceed with sanctioned entities. Contact compliance team.',
        );
    }

    public static function expiredDocument(string $documentType, \DateTimeImmutable $expiryDate): self
    {
        return new self(
            field: $documentType,
            code: 'EXPIRED_DOCUMENT',
            message: "{$documentType} expired on {$expiryDate->format('Y-m-d')}",
            severity: 'error',
            isBlocking: true,
            rejectedValue: $expiryDate,
            suggestion: 'Please upload a valid, non-expired document',
        );
    }

    public static function expiringDocument(string $documentType, \DateTimeImmutable $expiryDate, int $daysRemaining): self
    {
        return new self(
            field: $documentType,
            code: 'EXPIRING_DOCUMENT',
            message: "{$documentType} expires in {$daysRemaining} days on {$expiryDate->format('Y-m-d')}",
            severity: 'warning',
            isBlocking: false,
            rejectedValue: $expiryDate,
            suggestion: 'Consider uploading an updated document soon',
        );
    }

    public static function missingCertification(string $certificationName, string $reason): self
    {
        return new self(
            field: 'certifications',
            code: 'MISSING_CERTIFICATION',
            message: "Required certification '{$certificationName}' is missing. Reason: {$reason}",
            severity: 'error',
            isBlocking: true,
            rejectedValue: $certificationName,
            suggestion: "Please upload {$certificationName} certification",
        );
    }

    public static function invalidBankAccount(string $reason): self
    {
        return new self(
            field: 'banking_details',
            code: 'INVALID_BANK_ACCOUNT',
            message: "Bank account validation failed: {$reason}",
            severity: 'error',
            isBlocking: true,
            rejectedValue: null,
            suggestion: 'Please verify and re-enter your bank account details',
        );
    }

    public static function taxIdMismatch(string $taxId, string $expectedFormat): self
    {
        return new self(
            field: 'tax_id',
            code: 'TAX_ID_MISMATCH',
            message: "Tax ID '{$taxId}' does not match expected format for jurisdiction",
            severity: 'error',
            isBlocking: true,
            rejectedValue: $taxId,
            suggestion: "Expected format: {$expectedFormat}",
        );
    }

    public static function warning(string $field, string $message, ?string $suggestion = null): self
    {
        return new self(
            field: $field,
            code: 'WARNING',
            message: $message,
            severity: 'warning',
            isBlocking: false,
            rejectedValue: null,
            suggestion: $suggestion,
        );
    }

    public function isBlocking(): bool
    {
        return $this->isBlocking && $this->severity === 'error';
    }

    public function isWarning(): bool
    {
        return $this->severity === 'warning';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
            'is_blocking' => $this->isBlocking,
            'rejected_value' => $this->rejectedValue,
            'suggestion' => $this->suggestion,
        ];
    }
}
