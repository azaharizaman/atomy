<?php

declare(strict_types=1);

namespace Nexus\Product\ValueObjects;

use JsonSerializable;
use Nexus\Product\Enums\BarcodeFormat;
use Nexus\Product\Exceptions\InvalidBarcodeException;

/**
 * Barcode Value Object
 *
 * Format-aware barcode with validation based on format type.
 * Immutable once created.
 */
final readonly class Barcode implements JsonSerializable
{
    /**
     * @param string $value The barcode value
     * @param BarcodeFormat $format The barcode format
     * @throws InvalidBarcodeException
     */
    public function __construct(
        public string $value,
        public BarcodeFormat $format
    ) {
        $this->validate();
    }

    /**
     * Validate barcode based on format
     *
     * @throws InvalidBarcodeException
     */
    private function validate(): void
    {
        $trimmed = trim($this->value);

        if ($trimmed === '') {
            throw InvalidBarcodeException::emptyValue();
        }

        // Format-specific validation
        match ($this->format) {
            BarcodeFormat::EAN13 => $this->validateEan13($trimmed),
            BarcodeFormat::UPCA => $this->validateUpca($trimmed),
            BarcodeFormat::CODE128 => $this->validateCode128($trimmed),
            BarcodeFormat::QR => $this->validateQr($trimmed),
            BarcodeFormat::CUSTOM => null, // No validation for custom formats
        };
    }

    /**
     * Validate EAN-13 format
     *
     * @param string $value
     * @throws InvalidBarcodeException
     */
    private function validateEan13(string $value): void
    {
        if (!preg_match('/^\d{13}$/', $value)) {
            throw InvalidBarcodeException::invalidEan13($value);
        }

        if (!$this->validateEan13Checksum($value)) {
            throw InvalidBarcodeException::invalidChecksum($value, 'EAN-13');
        }
    }

    /**
     * Validate EAN-13 checksum
     *
     * @param string $value
     * @return bool
     */
    private function validateEan13Checksum(string $value): bool
    {
        $digits = str_split($value);
        $checkDigit = (int) array_pop($digits);
        
        $sum = 0;
        foreach ($digits as $index => $digit) {
            $weight = ($index % 2 === 0) ? 1 : 3;
            $sum += (int) $digit * $weight;
        }
        
        $calculatedCheck = (10 - ($sum % 10)) % 10;
        return $calculatedCheck === $checkDigit;
    }

    /**
     * Validate UPC-A format
     *
     * @param string $value
     * @throws InvalidBarcodeException
     */
    private function validateUpca(string $value): void
    {
        if (!preg_match('/^\d{12}$/', $value)) {
            throw InvalidBarcodeException::invalidUpca($value);
        }

        if (!$this->validateUpcaChecksum($value)) {
            throw InvalidBarcodeException::invalidChecksum($value, 'UPC-A');
        }
    }

    /**
     * Validate UPC-A checksum
     *
     * @param string $value
     * @return bool
     */
    private function validateUpcaChecksum(string $value): bool
    {
        $digits = str_split($value);
        $checkDigit = (int) array_pop($digits);
        
        $sum = 0;
        foreach ($digits as $index => $digit) {
            $weight = ($index % 2 === 0) ? 3 : 1;
            $sum += (int) $digit * $weight;
        }
        
        $calculatedCheck = (10 - ($sum % 10)) % 10;
        return $calculatedCheck === $checkDigit;
    }

    /**
     * Validate Code 128 format
     *
     * @param string $value
     * @throws InvalidBarcodeException
     */
    private function validateCode128(string $value): void
    {
        if (strlen($value) > 255) {
            throw InvalidBarcodeException::barcodeTooLong($value, 'CODE-128', 255);
        }

        // Code 128 supports ASCII characters 0-127
        if (!preg_match('/^[\x00-\x7F]+$/', $value)) {
            throw InvalidBarcodeException::invalidCode128($value);
        }
    }

    /**
     * Validate QR code format
     *
     * @param string $value
     * @throws InvalidBarcodeException
     */
    private function validateQr(string $value): void
    {
        // QR codes can encode up to ~4,296 alphanumeric characters
        if (strlen($value) > 4296) {
            throw InvalidBarcodeException::barcodeTooLong($value, 'QR', 4296);
        }
    }

    /**
     * Get the barcode value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the barcode format
     *
     * @return BarcodeFormat
     */
    public function getFormat(): BarcodeFormat
    {
        return $this->format;
    }

    /**
     * Check if two barcodes are equal
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value && $this->format === $other->format;
    }

    /**
     * Convert to array representation
     *
     * @return array{value: string, format: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'format' => $this->format->value,
        ];
    }

    /**
     * Create from array representation
     *
     * @param array{value: string, format: string} $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['value'],
            BarcodeFormat::fromString($data['format'])
        );
    }

    /**
     * JSON serialization
     *
     * @return array{value: string, format: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
