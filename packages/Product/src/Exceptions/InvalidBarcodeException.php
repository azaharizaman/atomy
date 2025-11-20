<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown for invalid barcode data
 */
class InvalidBarcodeException extends ProductException
{
    public static function emptyValue(): self
    {
        return new self("Barcode value cannot be empty.");
    }

    public static function unknownFormat(string $format): self
    {
        return new self("Unknown barcode format '{$format}'.");
    }

    public static function invalidEan13(string $value): self
    {
        return new self("Invalid EAN-13 barcode '{$value}'. Must be exactly 13 digits.");
    }

    public static function invalidUpca(string $value): self
    {
        return new self("Invalid UPC-A barcode '{$value}'. Must be exactly 12 digits.");
    }

    public static function invalidCode128(string $value): self
    {
        return new self("Invalid CODE-128 barcode '{$value}'. Must contain only ASCII characters (0-127).");
    }

    public static function invalidChecksum(string $value, string $format): self
    {
        return new self("Invalid {$format} checksum for barcode '{$value}'.");
    }

    public static function barcodeTooLong(string $value, string $format, int $maxLength): self
    {
        $actualLength = strlen($value);
        return new self(
            "Barcode '{$value}' exceeds maximum length for {$format}. " .
            "Expected max {$maxLength} characters, got {$actualLength}."
        );
    }
}
