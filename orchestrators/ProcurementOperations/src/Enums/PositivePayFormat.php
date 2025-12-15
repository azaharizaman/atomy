<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Positive Pay file format variants.
 *
 * Different banks require different formats for Positive Pay files.
 * This enum defines the supported format variants.
 */
enum PositivePayFormat: string
{
    /**
     * Standard CSV format.
     * Most widely supported format with comma-separated values.
     * Columns: Account Number, Check Number, Amount, Payee Name, Issue Date
     */
    case STANDARD_CSV = 'standard_csv';

    /**
     * BAI2 format variant for Positive Pay.
     * Uses BAI2 transaction codes for check issuance.
     */
    case BAI2 = 'bai2';

    /**
     * Bank of America specific format.
     * Fixed-width format with specific field positions.
     */
    case BANK_OF_AMERICA = 'boa';

    /**
     * Wells Fargo format.
     * Fixed-width with Wells Fargo specific requirements.
     */
    case WELLS_FARGO = 'wells_fargo';

    /**
     * Chase format.
     * JPMorgan Chase specific Positive Pay format.
     */
    case CHASE = 'chase';

    /**
     * Citi format.
     * Citibank specific Positive Pay format.
     */
    case CITI = 'citi';

    /**
     * Get human-readable name for the format.
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD_CSV => 'Standard CSV',
            self::BAI2 => 'BAI2 Format',
            self::BANK_OF_AMERICA => 'Bank of America',
            self::WELLS_FARGO => 'Wells Fargo',
            self::CHASE => 'Chase',
            self::CITI => 'Citibank',
        };
    }

    /**
     * Get the file extension for this format.
     */
    public function extension(): string
    {
        return match ($this) {
            self::STANDARD_CSV => 'csv',
            self::BAI2 => 'bai',
            default => 'txt',
        };
    }

    /**
     * Check if format is fixed-width.
     */
    public function isFixedWidth(): bool
    {
        return match ($this) {
            self::STANDARD_CSV => false,
            self::BAI2 => false,
            default => true,
        };
    }

    /**
     * Check if format requires header record.
     */
    public function requiresHeader(): bool
    {
        return match ($this) {
            self::STANDARD_CSV => true,
            self::BAI2 => true,
            default => false,
        };
    }

    /**
     * Check if format requires trailer record.
     */
    public function requiresTrailer(): bool
    {
        return match ($this) {
            self::BAI2 => true,
            self::BANK_OF_AMERICA => true,
            self::WELLS_FARGO => true,
            default => false,
        };
    }

    /**
     * Get the date format required by this format.
     */
    public function dateFormat(): string
    {
        return match ($this) {
            self::STANDARD_CSV => 'Y-m-d',
            self::BAI2 => 'Ymd',
            self::BANK_OF_AMERICA => 'mdY',
            self::WELLS_FARGO => 'Ymd',
            self::CHASE => 'mdY',
            self::CITI => 'Ymd',
        };
    }
}
