<?php

declare(strict_types=1);

namespace Nexus\Product\Enums;

use Nexus\Product\Exceptions\InvalidBarcodeException;

/**
 * Barcode Format Enum
 *
 * Defines supported barcode formats with validation rules.
 */
enum BarcodeFormat: string
{
    /**
     * European Article Number (13 digits)
     * Most common retail barcode globally
     */
    case EAN13 = 'ean13';

    /**
     * Universal Product Code (12 digits)
     * Common in North America
     */
    case UPCA = 'upca';

    /**
     * Code 128 (variable length alphanumeric)
     * High-density barcode for logistics
     */
    case CODE128 = 'code128';

    /**
     * QR Code (2D matrix barcode)
     * Can encode large amounts of data
     */
    case QR = 'qr';

    /**
     * Custom/proprietary format
     * Internal use, no validation
     */
    case CUSTOM = 'custom';

    /**
     * Create from string value
     *
     * @param string $value
     * @return self
     * @throws InvalidBarcodeException
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'ean13', 'ean-13' => self::EAN13,
            'upca', 'upc-a', 'upc' => self::UPCA,
            'code128', 'code-128' => self::CODE128,
            'qr', 'qr-code', 'qrcode' => self::QR,
            'custom' => self::CUSTOM,
            default => throw InvalidBarcodeException::unknownFormat($value),
        };
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::EAN13 => 'EAN-13',
            self::UPCA => 'UPC-A',
            self::CODE128 => 'Code 128',
            self::QR => 'QR Code',
            self::CUSTOM => 'Custom Format',
        };
    }

    /**
     * Check if format requires numeric-only value
     *
     * @return bool
     */
    public function isNumericOnly(): bool
    {
        return match ($this) {
            self::EAN13, self::UPCA => true,
            self::CODE128, self::QR, self::CUSTOM => false,
        };
    }

    /**
     * Get expected length for fixed-length formats
     *
     * @return int|null Null for variable-length formats
     */
    public function getExpectedLength(): ?int
    {
        return match ($this) {
            self::EAN13 => 13,
            self::UPCA => 12,
            self::CODE128, self::QR, self::CUSTOM => null,
        };
    }

    /**
     * Check if format supports checksum validation
     *
     * @return bool
     */
    public function supportsChecksum(): bool
    {
        return match ($this) {
            self::EAN13, self::UPCA => true,
            self::CODE128, self::QR, self::CUSTOM => false,
        };
    }
}
