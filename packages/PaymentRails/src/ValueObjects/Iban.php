<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\PaymentRails\Exceptions\InvalidIbanException;

/**
 * Represents an International Bank Account Number (IBAN).
 *
 * IBAN is an internationally agreed system of identifying bank accounts
 * across national borders to facilitate communication and processing of
 * cross-border transactions.
 *
 * Format:
 * - 2 letter country code (ISO 3166-1 alpha-2)
 * - 2 check digits
 * - Up to 30 alphanumeric characters (BBAN - Basic Bank Account Number)
 *
 * The total length varies by country (e.g., DE: 22, GB: 22, MY: 24).
 */
final class Iban
{
    private const IBAN_PATTERN = '/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/';

    /**
     * Country-specific IBAN lengths.
     *
     * @var array<string, int>
     */
    private const COUNTRY_LENGTHS = [
        'AL' => 28, 'AD' => 24, 'AT' => 20, 'AZ' => 28, 'BH' => 22,
        'BY' => 28, 'BE' => 16, 'BA' => 20, 'BR' => 29, 'BG' => 22,
        'CR' => 22, 'HR' => 21, 'CY' => 28, 'CZ' => 24, 'DK' => 18,
        'DO' => 28, 'TL' => 23, 'EE' => 20, 'FO' => 18, 'FI' => 18,
        'FR' => 27, 'GE' => 22, 'DE' => 22, 'GI' => 23, 'GR' => 27,
        'GL' => 18, 'GT' => 28, 'HU' => 28, 'IS' => 26, 'IQ' => 23,
        'IE' => 22, 'IL' => 23, 'IT' => 27, 'JO' => 30, 'KZ' => 20,
        'XK' => 20, 'KW' => 30, 'LV' => 21, 'LB' => 28, 'LI' => 21,
        'LT' => 20, 'LU' => 20, 'MK' => 19, 'MT' => 31, 'MR' => 27,
        'MU' => 30, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'NL' => 18,
        'NO' => 15, 'PK' => 24, 'PS' => 29, 'PL' => 28, 'PT' => 25,
        'QA' => 29, 'RO' => 24, 'LC' => 32, 'SM' => 27, 'ST' => 25,
        'SA' => 24, 'RS' => 22, 'SC' => 31, 'SK' => 24, 'SI' => 19,
        'ES' => 24, 'SE' => 24, 'CH' => 21, 'TN' => 24, 'TR' => 26,
        'UA' => 29, 'AE' => 23, 'GB' => 22, 'VG' => 24, 'MY' => 24,
        'SG' => 21,
    ];

    /**
     * @var string The normalized IBAN (uppercase, no spaces)
     */
    public readonly string $value;

    /**
     * @throws InvalidIbanException
     */
    public function __construct(string $value)
    {
        $value = strtoupper(preg_replace('/\s+/', '', $value) ?? '');

        if (!self::isValidFormat($value)) {
            throw InvalidIbanException::invalidFormat($value);
        }

        $countryCode = mb_substr($value, 0, 2);
        if (isset(self::COUNTRY_LENGTHS[$countryCode]) && mb_strlen($value) !== self::COUNTRY_LENGTHS[$countryCode]) {
            throw InvalidIbanException::invalidLengthForCountry($value, $countryCode, self::COUNTRY_LENGTHS[$countryCode]);
        }

        if (!self::validateCheckDigits($value)) {
            throw InvalidIbanException::invalidCheckDigits($value);
        }

        $this->value = $value;
    }

    /**
     * Create an IBAN from a string value.
     *
     * @throws InvalidIbanException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create an IBAN, returning null if invalid.
     */
    public static function tryFromString(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidIbanException) {
            return null;
        }
    }

    /**
     * Get the country code (first 2 characters).
     */
    public function getCountryCode(): string
    {
        return mb_substr($this->value, 0, 2);
    }

    /**
     * Get the check digits (characters 3-4).
     */
    public function getCheckDigits(): string
    {
        return mb_substr($this->value, 2, 2);
    }

    /**
     * Get the BBAN (Basic Bank Account Number).
     */
    public function getBban(): string
    {
        return mb_substr($this->value, 4);
    }

    /**
     * Get the bank code portion (varies by country).
     *
     * This method returns the first 4-8 characters of BBAN which typically
     * contains the bank identifier. The exact length varies by country.
     */
    public function getBankCode(): string
    {
        $countryCode = $this->getCountryCode();

        return match ($countryCode) {
            'DE' => mb_substr($this->value, 4, 8),
            'GB' => mb_substr($this->value, 4, 4),
            'FR' => mb_substr($this->value, 4, 5),
            'MY' => mb_substr($this->value, 4, 4),
            default => mb_substr($this->value, 4, 4),
        };
    }

    /**
     * Get formatted IBAN with spaces (groups of 4).
     */
    public function formatted(): string
    {
        return trim(chunk_split($this->value, 4, ' '));
    }

    /**
     * Get the masked IBAN for display.
     *
     * Shows first 4 and last 4 characters, masks the rest.
     */
    public function masked(): string
    {
        $length = mb_strlen($this->value);

        if ($length <= 8) {
            return $this->value;
        }

        $start = mb_substr($this->value, 0, 4);
        $end = mb_substr($this->value, -4);
        $masked = str_repeat('*', $length - 8);

        return $start . $masked . $end;
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
     * Check equality with another IBAN.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Check if the IBAN is from a SEPA (Single Euro Payments Area) country.
     */
    public function isSepaCountry(): bool
    {
        $sepaCountries = [
            'AT', 'BE', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES',
            'FI', 'FR', 'GB', 'GI', 'GR', 'HR', 'HU', 'IE', 'IS', 'IT',
            'LI', 'LT', 'LU', 'LV', 'MC', 'MT', 'NL', 'NO', 'PL', 'PT',
            'RO', 'SE', 'SI', 'SK', 'SM', 'VA',
        ];

        return in_array($this->getCountryCode(), $sepaCountries, true);
    }

    /**
     * Validate the basic format of an IBAN.
     */
    private static function isValidFormat(string $value): bool
    {
        return preg_match(self::IBAN_PATTERN, $value) === 1;
    }

    /**
     * Validate the check digits using ISO 7064 Mod 97-10 algorithm.
     */
    private static function validateCheckDigits(string $iban): bool
    {
        // Move first 4 characters to end
        $rearranged = mb_substr($iban, 4) . mb_substr($iban, 0, 4);

        // Convert letters to numbers (A=10, B=11, ..., Z=35)
        $converted = '';
        for ($i = 0, $len = mb_strlen($rearranged); $i < $len; $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $converted .= (string) (ord($char) - 55);
            } else {
                $converted .= $char;
            }
        }

        // Perform mod 97 operation (handle large numbers by processing in chunks)
        $remainder = 0;
        for ($i = 0, $len = strlen($converted); $i < $len; $i++) {
            $remainder = (int) (($remainder . $converted[$i]) % 97);
        }

        return $remainder === 1;
    }
}
