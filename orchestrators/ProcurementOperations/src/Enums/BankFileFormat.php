<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Bank file format types supported by the bank file generation system.
 */
enum BankFileFormat: string
{
    /**
     * NACHA ACH format for US domestic electronic payments.
     * Used for direct deposit, vendor payments, and B2B transactions.
     */
    case NACHA = 'nacha';

    /**
     * Positive Pay format for check fraud prevention.
     * Sent to banks to validate check presentments.
     */
    case POSITIVE_PAY = 'positive_pay';

    /**
     * SWIFT MT101 format for international wire transfers.
     * Request for Transfer message type.
     */
    case SWIFT_MT101 = 'swift_mt101';

    /**
     * ISO 20022 pain.001 format for credit transfers.
     * XML-based international standard.
     */
    case ISO20022 = 'iso20022';

    /**
     * BAI2 format for balance and transaction reporting.
     * Used for bank statement imports.
     */
    case BAI2 = 'bai2';

    /**
     * Get human-readable name for the format.
     */
    public function label(): string
    {
        return match ($this) {
            self::NACHA => 'NACHA ACH',
            self::POSITIVE_PAY => 'Positive Pay',
            self::SWIFT_MT101 => 'SWIFT MT101',
            self::ISO20022 => 'ISO 20022 pain.001',
            self::BAI2 => 'BAI2',
        };
    }

    /**
     * Get the default file extension for the format.
     */
    public function extension(): string
    {
        return match ($this) {
            self::NACHA => 'ach',
            self::POSITIVE_PAY => 'txt',
            self::SWIFT_MT101 => 'mt',
            self::ISO20022 => 'xml',
            self::BAI2 => 'bai',
        };
    }

    /**
     * Check if format supports multiple payment methods.
     */
    public function supportsMultiplePaymentMethods(): bool
    {
        return match ($this) {
            self::NACHA => false, // ACH only
            self::POSITIVE_PAY => false, // Check only
            self::SWIFT_MT101 => false, // Wire only
            self::ISO20022 => true, // Various payment types
            self::BAI2 => true, // Reporting format
        };
    }

    /**
     * Check if format is for domestic (US) payments only.
     */
    public function isDomesticOnly(): bool
    {
        return match ($this) {
            self::NACHA => true,
            self::POSITIVE_PAY => true,
            self::SWIFT_MT101 => false,
            self::ISO20022 => false,
            self::BAI2 => false,
        };
    }
}
