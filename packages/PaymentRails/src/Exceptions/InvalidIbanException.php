<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

/**
 * Exception thrown when an IBAN is invalid.
 */
final class InvalidIbanException extends PaymentRailException
{
    /**
     * @param string $iban
     * @param string $reason
     * @param \Throwable|null $previous
     */
    public function __construct(
        private readonly string $iban,
        string $reason = 'Invalid format',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Invalid IBAN '{$this->maskIban()}': {$reason}",
            railType: 'WIRE',
            context: [
                'iban_last4' => $this->getLastFour(),
                'country_code' => $this->getCountryCode(),
                'reason' => $reason,
            ],
            previous: $previous,
        );
    }

    /**
     * Get the last four characters of the IBAN.
     */
    public function getLastFour(): string
    {
        return substr($this->iban, -4);
    }

    /**
     * Get the country code from the IBAN.
     */
    public function getCountryCode(): string
    {
        return substr($this->iban, 0, 2);
    }

    /**
     * Mask the IBAN for display.
     */
    private function maskIban(): string
    {
        if (strlen($this->iban) <= 8) {
            return $this->iban;
        }

        return substr($this->iban, 0, 4) .
               str_repeat('*', strlen($this->iban) - 8) .
               substr($this->iban, -4);
    }

    /**
     * Create for invalid length.
     */
    public static function invalidLength(string $iban, int $expectedLength): self
    {
        $actualLength = strlen($iban);
        $countryCode = substr($iban, 0, 2);
        return new self(
            $iban,
            "Invalid length for {$countryCode}: expected {$expectedLength}, got {$actualLength}"
        );
    }

    /**
     * Create for unsupported country.
     */
    public static function unsupportedCountry(string $iban): self
    {
        $countryCode = substr($iban, 0, 2);
        return new self($iban, "Unsupported country code '{$countryCode}'");
    }

    /**
     * Create for invalid checksum.
     */
    public static function invalidChecksum(string $iban): self
    {
        return new self($iban, 'Failed checksum validation (ISO 7064 Mod 97-10)');
    }

    /**
     * Create for invalid characters.
     */
    public static function invalidCharacters(string $iban): self
    {
        return new self($iban, 'IBAN contains invalid characters (must be alphanumeric)');
    }

    /**
     * Create for invalid structure.
     */
    public static function invalidStructure(string $iban, string $expectedPattern): self
    {
        $countryCode = substr($iban, 0, 2);
        return new self(
            $iban,
            "Invalid BBAN structure for {$countryCode}: expected pattern '{$expectedPattern}'"
        );
    }

    /**
     * Create for missing check digits.
     */
    public static function missingCheckDigits(string $iban): self
    {
        return new self($iban, 'Check digits (positions 3-4) are missing or invalid');
    }
}
