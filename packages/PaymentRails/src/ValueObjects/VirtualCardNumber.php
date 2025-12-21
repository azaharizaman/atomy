<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\PaymentRails\Exceptions\InvalidVirtualCardNumberException;

/**
 * Represents a virtual card number.
 *
 * Virtual card numbers follow the standard credit card number format
 * with Luhn algorithm validation.
 */
final class VirtualCardNumber
{
    /**
     * @var string The 16-digit card number
     */
    public readonly string $value;

    /**
     * @throws InvalidVirtualCardNumberException
     */
    public function __construct(string $value)
    {
        $value = preg_replace('/[\s-]/', '', $value) ?? '';

        if (!ctype_digit($value)) {
            throw InvalidVirtualCardNumberException::nonNumeric($value);
        }

        if (mb_strlen($value) < 13 || mb_strlen($value) > 19) {
            throw InvalidVirtualCardNumberException::invalidLength($value);
        }

        if (!self::validateLuhn($value)) {
            throw InvalidVirtualCardNumberException::invalidChecksum($value);
        }

        $this->value = $value;
    }

    /**
     * Create a virtual card number from a string.
     *
     * @throws InvalidVirtualCardNumberException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create a virtual card number, returning null if invalid.
     */
    public static function tryFromString(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidVirtualCardNumberException) {
            return null;
        }
    }

    /**
     * Get the BIN (Bank Identification Number) - first 6 digits.
     */
    public function getBin(): string
    {
        return mb_substr($this->value, 0, 6);
    }

    /**
     * Get the last 4 digits.
     */
    public function getLastFour(): string
    {
        return mb_substr($this->value, -4);
    }

    /**
     * Get the card network based on the BIN.
     */
    public function getCardNetwork(): string
    {
        $firstDigit = $this->value[0];
        $firstTwo = mb_substr($this->value, 0, 2);
        $firstFour = mb_substr($this->value, 0, 4);

        // Visa: starts with 4
        if ($firstDigit === '4') {
            return 'visa';
        }

        // Mastercard: starts with 51-55 or 2221-2720
        if (($firstTwo >= '51' && $firstTwo <= '55')
            || ($firstFour >= '2221' && $firstFour <= '2720')) {
            return 'mastercard';
        }

        // American Express: starts with 34 or 37
        if ($firstTwo === '34' || $firstTwo === '37') {
            return 'amex';
        }

        // Discover: starts with 6011, 622126-622925, 644-649, 65
        if (mb_substr($this->value, 0, 4) === '6011'
            || ($firstTwo >= '64' && $firstTwo <= '65')
            || (mb_substr($this->value, 0, 6) >= '622126' && mb_substr($this->value, 0, 6) <= '622925')) {
            return 'discover';
        }

        return 'unknown';
    }

    /**
     * Get the masked card number for display.
     */
    public function masked(): string
    {
        $length = mb_strlen($this->value);
        $masked = str_repeat('*', $length - 4);

        return $masked . $this->getLastFour();
    }

    /**
     * Get the formatted card number with spaces.
     */
    public function formatted(): string
    {
        // AmEx uses 4-6-5 format
        if ($this->getCardNetwork() === 'amex') {
            return sprintf(
                '%s %s %s',
                mb_substr($this->value, 0, 4),
                mb_substr($this->value, 4, 6),
                mb_substr($this->value, 10)
            );
        }

        // Standard 4-4-4-4 format
        return implode(' ', str_split($this->value, 4));
    }

    /**
     * Get the string representation.
     */
    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another card number.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Validate using the Luhn algorithm.
     */
    private static function validateLuhn(string $number): bool
    {
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int) $number[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
