<?php

declare(strict_types=1);

namespace Nexus\Currency\ValueObjects;

use InvalidArgumentException;

/**
 * Currency Value Object
 *
 * Immutable representation of an ISO 4217 currency with metadata.
 *
 * @package Nexus\Currency\ValueObjects
 */
final readonly class Currency
{
    /**
     * @param string $code ISO 4217 3-letter currency code (e.g., "USD", "EUR", "JPY")
     * @param string $name Full currency name (e.g., "US Dollar", "Euro")
     * @param string $symbol Currency symbol (e.g., "$", "€", "¥")
     * @param int $decimalPlaces Number of decimal places per ISO 4217 (0 for JPY, 2 for USD, 3 for BHD)
     * @param string $numericCode ISO 4217 numeric code (e.g., "840" for USD)
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
        public int $decimalPlaces,
        public string $numericCode
    ) {
        $this->validateCode($code);
        $this->validateDecimalPlaces($decimalPlaces);
        $this->validateNumericCode($numericCode);
    }

    /**
     * Create Currency from minimal data (for testing or simple use cases)
     */
    public static function create(
        string $code,
        string $name,
        string $symbol,
        int $decimalPlaces = 2,
        ?string $numericCode = null
    ): self {
        return new self(
            $code,
            $name,
            $symbol,
            $decimalPlaces,
            $numericCode ?? '999' // Generic placeholder
        );
    }

    /**
     * Get the ISO 4217 currency code
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the full currency name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the currency symbol
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * Get the number of decimal places
     */
    public function getDecimalPlaces(): int
    {
        return $this->decimalPlaces;
    }

    /**
     * Get the ISO 4217 numeric code
     */
    public function getNumericCode(): string
    {
        return $this->numericCode;
    }

    /**
     * Check if this currency requires no decimal places (e.g., JPY, KRW)
     */
    public function isZeroDecimal(): bool
    {
        return $this->decimalPlaces === 0;
    }

    /**
     * Check if this currency requires three decimal places (e.g., BHD, KWD)
     */
    public function isThreeDecimal(): bool
    {
        return $this->decimalPlaces === 3;
    }

    /**
     * Format an amount according to currency rules
     *
     * @param string $amount The amount to format (BCMath string)
     * @param bool $includeSymbol Whether to include the currency symbol
     * @param bool $includeCode Whether to include the currency code
     */
    public function formatAmount(string $amount, bool $includeSymbol = true, bool $includeCode = false): string
    {
        $rounded = bcadd($amount, '0', $this->decimalPlaces);
        // BCMath-safe formatting: split integer and fractional parts
        $negative = false;
        $roundedStr = $rounded;
        if (str_starts_with($roundedStr, '-')) {
            $negative = true;
            $roundedStr = substr($roundedStr, 1);
        }
        $partsArr = explode('.', $roundedStr, 2);
        $integerPart = $partsArr[0];
        $fractionalPart = $partsArr[1] ?? '';
        // Pad/truncate fractional part
        $fractionalPart = str_pad($fractionalPart, $this->decimalPlaces, '0');
        if (strlen($fractionalPart) > $this->decimalPlaces) {
            $fractionalPart = substr($fractionalPart, 0, $this->decimalPlaces);
        }
        // Add thousands separator to integer part
        $integerPartWithSep = '';
        $len = strlen($integerPart);
        for ($i = 0; $i < $len; $i++) {
            if ($i > 0 && (($len - $i) % 3 === 0)) {
                $integerPartWithSep .= ',';
            }
            $integerPartWithSep .= $integerPart[$i];
        }
        $formatted = $integerPartWithSep;
        if ($this->decimalPlaces > 0) {
            $formatted .= '.' . $fractionalPart;
        }
        if ($negative) {
            $formatted = '-' . $formatted;
        }

        $parts = [];
        if ($includeSymbol) {
            $parts[] = $this->symbol;
        }
        $parts[] = $formatted;
        if ($includeCode) {
            $parts[] = $this->code;
        }

        return implode(' ', $parts);
    }

    /**
     * Check if two currencies are the same
     */
    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return "{$this->code} ({$this->name})";
    }

    private function validateCode(string $code): void
    {
        if (strlen($code) !== 3) {
            throw new InvalidArgumentException("Currency code must be 3 characters, got: {$code}");
        }

        if (!ctype_upper($code)) {
            throw new InvalidArgumentException("Currency code must be uppercase: {$code}");
        }

        if (!ctype_alpha($code)) {
            throw new InvalidArgumentException("Currency code must contain only letters: {$code}");
        }
    }

    private function validateDecimalPlaces(int $decimalPlaces): void
    {
        if ($decimalPlaces < 0 || $decimalPlaces > 3) {
            throw new InvalidArgumentException(
                "Decimal places must be between 0 and 3 per ISO 4217, got: {$decimalPlaces}"
            );
        }
    }

    private function validateNumericCode(string $numericCode): void
    {
        if (!ctype_digit($numericCode)) {
            throw new InvalidArgumentException("Numeric code must contain only digits: {$numericCode}");
        }

        if (strlen($numericCode) !== 3) {
            throw new InvalidArgumentException("Numeric code must be 3 digits, got: {$numericCode}");
        }
    }
}
