<?php

declare(strict_types=1);

namespace Nexus\Crypto\Enums;

/**
 * Masking Pattern Enum
 *
 * Predefined masking patterns for common sensitive data types.
 * Each pattern defines how to partially obscure data while maintaining recognizable format.
 *
 * All patterns follow PCI-DSS, HIPAA, and GDPR best practices for data minimization in display.
 */
enum MaskingPattern: string
{
    /**
     * Email masking: j*******@example.com
     *
     * Shows first character of local part and full domain.
     * Allows user to recognize their email while hiding details.
     */
    case EMAIL = 'email';

    /**
     * Phone masking: +1 (***) ***-4567
     *
     * Shows country code (if present) and last 4 digits.
     * Compliant with PCI-DSS for phone number display.
     */
    case PHONE = 'phone';

    /**
     * Credit card masking: ****-****-****-1234
     *
     * Shows only last 4 digits (PCI-DSS compliant).
     * Full Primary Account Number (PAN) is never displayed.
     */
    case CREDIT_CARD = 'credit_card';

    /**
     * IBAN masking: DE**************3000
     *
     * Shows country code and last 4 characters.
     * Allows identification of bank country while hiding account details.
     */
    case IBAN = 'iban';

    /**
     * Name masking: J*** D**
     *
     * Shows first letter of each name part.
     * Preserves structure while hiding identity.
     */
    case NAME = 'name';

    /**
     * Address masking: 123 **** Street, ****
     *
     * Preserves street number, masks street name and additional details.
     * Maintains format while reducing identifiability.
     */
    case ADDRESS = 'address';

    /**
     * Date of birth masking: ****-**-15
     *
     * Shows only day of month.
     * Reduces re-identification risk while preserving some utility.
     */
    case DATE_OF_BIRTH = 'date_of_birth';

    /**
     * Full redaction: [REDACTED]
     *
     * Complete replacement with redaction marker.
     * Maximum privacy, zero information leakage.
     */
    case FULL_REDACTION = 'full_redaction';

    /**
     * Get display-friendly label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => 'Email Address',
            self::PHONE => 'Phone Number',
            self::CREDIT_CARD => 'Credit Card',
            self::IBAN => 'IBAN / Bank Account',
            self::NAME => 'Personal Name',
            self::ADDRESS => 'Mailing Address',
            self::DATE_OF_BIRTH => 'Date of Birth',
            self::FULL_REDACTION => 'Full Redaction',
        };
    }

    /**
     * Get example output for documentation
     */
    public function getExample(): string
    {
        return match ($this) {
            self::EMAIL => 'j*******@example.com',
            self::PHONE => '+1 (***) ***-4567',
            self::CREDIT_CARD => '****-****-****-1234',
            self::IBAN => 'DE**************3000',
            self::NAME => 'J*** D**',
            self::ADDRESS => '123 **** Street, ****',
            self::DATE_OF_BIRTH => '****-**-15',
            self::FULL_REDACTION => '[REDACTED]',
        };
    }

    /**
     * Get the default masking character for this pattern
     */
    public function getDefaultMaskChar(): string
    {
        return '*';
    }

    /**
     * Check if this pattern preserves format structure
     *
     * Format-preserving patterns maintain the visual structure (separators, length hints)
     * while masking the actual data.
     */
    public function isFormatPreserving(): bool
    {
        return match ($this) {
            self::EMAIL => true,
            self::PHONE => true,
            self::CREDIT_CARD => true,
            self::IBAN => true,
            self::NAME => true,
            self::ADDRESS => true,
            self::DATE_OF_BIRTH => true,
            self::FULL_REDACTION => false,
        };
    }

    /**
     * Get compliance standards this pattern satisfies
     *
     * @return array<string>
     */
    public function getComplianceStandards(): array
    {
        return match ($this) {
            self::CREDIT_CARD => ['PCI-DSS'],
            self::EMAIL, self::PHONE, self::NAME, self::ADDRESS => ['GDPR', 'HIPAA'],
            self::DATE_OF_BIRTH => ['GDPR', 'HIPAA', 'PCI-DSS'],
            self::IBAN => ['PSD2', 'GDPR'],
            self::FULL_REDACTION => ['GDPR', 'HIPAA', 'PCI-DSS', 'PDPA'],
        };
    }
}
