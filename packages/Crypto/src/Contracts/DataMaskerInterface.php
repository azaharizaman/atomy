<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\MaskingPattern;

/**
 * Data Masker Interface
 *
 * Provides data masking for secure display and logging.
 * Masks sensitive data while preserving recognizable format.
 *
 * Key Concepts:
 * - **Masking**: Partial obscuring of data for display purposes
 * - **Redaction**: Complete removal/replacement of sensitive data
 *
 * Use Cases:
 * - Display masked credit cards in UI (****-****-****-1234)
 * - Log masked emails for debugging (j***@example.com)
 * - Show partial phone numbers to users (+1 (***) ***-7890)
 * - Mask SSN/National IDs in reports (***-**-6789)
 *
 * Compliance:
 * - PCI-DSS: Credit card masking requirements
 * - HIPAA: PHI display restrictions
 * - GDPR: Data minimization principle
 *
 * Note: Masking is NOT encryption - masked data may be partially recoverable.
 * Use encryption for data at rest, masking only for display purposes.
 */
interface DataMaskerInterface
{
    /**
     * Mask data using predefined pattern
     *
     * Applies a standard masking pattern appropriate for the data type.
     * Patterns are designed to comply with industry standards (PCI-DSS, HIPAA, GDPR).
     *
     * @param string $data The sensitive data to mask
     * @param MaskingPattern $pattern Pattern to apply
     * @return string Masked data preserving format structure
     */
    public function mask(string $data, MaskingPattern $pattern): string;

    /**
     * Mask data using custom pattern
     *
     * Pattern characters:
     * - '#' = preserve character
     * - '*' = mask character
     * - Any other = literal character
     *
     * Example patterns:
     * - "####-****-****-####" → "1234-****-****-5678"
     * - "###.*@*" → "joh.*@*" (email, first 3 chars + domain indicator)
     *
     * If data is shorter than pattern, masking applies to available characters.
     * If data is longer than pattern, pattern repeats or truncates based on implementation.
     *
     * @param string $data The sensitive data to mask
     * @param string $pattern Custom masking pattern using # (keep) and * (mask)
     * @param string $maskChar Character to use for masking (default: '*')
     * @return string Masked data
     */
    public function maskWithPattern(string $data, string $pattern, string $maskChar = '*'): string;

    /**
     * Mask email address
     *
     * Format: j***@example.com
     * - Shows first character of local part
     * - Preserves domain for recognition
     * - Masks middle characters
     *
     * @param string $email Email address to mask
     * @return string Masked email
     */
    public function maskEmail(string $email): string;

    /**
     * Mask phone number
     *
     * Format: +1 (***) ***-4567
     * - Preserves country code if present
     * - Shows last 4 digits
     * - Masks area code and exchange
     *
     * @param string $phone Phone number (any format)
     * @return string Masked phone number
     */
    public function maskPhone(string $phone): string;

    /**
     * Mask credit card number
     *
     * Format: ****-****-****-1234
     * - Shows only last 4 digits (PCI-DSS compliant)
     * - Formats with dashes for readability
     *
     * @param string $cardNumber Credit card number (with or without separators)
     * @return string PCI-DSS compliant masked card number
     */
    public function maskCreditCard(string $cardNumber): string;

    /**
     * Mask national ID / SSN
     *
     * Format varies by country:
     * - MY (Malaysia): ******-**-5678 (shows last 4 of 12-digit IC)
     * - US (USA): ***-**-6789 (shows last 4 of SSN)
     * - GB/UK (Britain): AB******* (shows first 2 of NIN)
     * - SG (Singapore): ****567D (shows last 4 of NRIC)
     * - Other: Shows first 2 and last 2 characters
     *
     * @param string $nationalId National ID or SSN
     * @param string $country ISO 3166-1 alpha-2 country code (required)
     * @return string Masked national ID
     */
    public function maskNationalId(string $nationalId, string $country): string;

    /**
     * Mask IBAN
     *
     * Format: DE**************3000
     * - Shows country code (2 chars)
     * - Shows last 4 digits
     * - Masks middle portion
     *
     * @param string $iban International Bank Account Number
     * @return string Masked IBAN
     */
    public function maskIban(string $iban): string;

    /**
     * Mask personal name
     *
     * Format: J*** D**
     * - Shows first letter of each name part
     * - Masks remaining characters
     * - Preserves word boundaries
     *
     * @param string $name Full name (first, last, or full)
     * @return string Masked name
     */
    public function maskName(string $name): string;

    /**
     * Mask address
     *
     * Format: 123 **** Street, ****
     * - Preserves street number
     * - Masks street name and additional details
     * - Maintains format structure
     *
     * @param string $address Mailing address
     * @return string Masked address
     */
    public function maskAddress(string $address): string;

    /**
     * Mask date of birth
     *
     * Format: ****-**-15
     * - Shows only day of month
     * - Masks year and month
     * - Reduces re-identification risk
     *
     * @param string $dateOfBirth Date in various formats (YYYY-MM-DD preferred)
     * @return string Masked date
     */
    public function maskDateOfBirth(string $dateOfBirth): string;

    /**
     * Redact data completely
     *
     * Replaces entire data with redaction marker.
     * Use when no partial data should be visible.
     *
     * @param string $data Data to redact
     * @param string $redactionMarker Replacement text (default: '[REDACTED]')
     * @return string Redaction marker
     */
    public function redact(string $data, string $redactionMarker = '[REDACTED]'): string;

    /**
     * Check if a value is already masked
     *
     * Useful to prevent double-masking.
     *
     * @param string $value Value to check
     * @param string $maskChar Expected masking character
     * @return bool True if value appears to be already masked
     */
    public function isAlreadyMasked(string $value, string $maskChar = '*'): bool;
}
