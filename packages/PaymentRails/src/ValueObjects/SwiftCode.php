<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\PaymentRails\Exceptions\InvalidSwiftCodeException;

/**
 * Represents a SWIFT/BIC code for international bank identification.
 *
 * A SWIFT code (also known as BIC - Bank Identifier Code) is an 8 or 11
 * character code that identifies banks and financial institutions globally.
 *
 * Format:
 * - AAAA: Bank code (4 letters)
 * - BB: Country code (ISO 3166-1 alpha-2)
 * - CC: Location code (2 letters or digits)
 * - DDD: Branch code (3 letters or digits) - optional
 *
 * Example: DEUTDEFF (Deutsche Bank, Germany, Frankfurt)
 *          DEUTDEFF500 (Deutsche Bank, Germany, Frankfurt, Bad Homburg branch)
 */
final class SwiftCode
{
    private const SWIFT_8_PATTERN = '/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}$/';
    private const SWIFT_11_PATTERN = '/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}[A-Z0-9]{3}$/';

    /**
     * @var string The normalized SWIFT/BIC code (uppercase, 8 or 11 chars)
     */
    public readonly string $value;

    /**
     * @throws InvalidSwiftCodeException
     */
    public function __construct(string $value)
    {
        $value = strtoupper(trim($value));

        if (!self::isValidFormat($value)) {
            throw InvalidSwiftCodeException::invalidFormat($value);
        }

        $this->value = $value;
    }

    /**
     * Create a SWIFT code from a string value.
     *
     * @throws InvalidSwiftCodeException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create a SWIFT code, returning null if invalid.
     */
    public static function tryFromString(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidSwiftCodeException) {
            return null;
        }
    }

    /**
     * Get the bank code (first 4 characters).
     */
    public function getBankCode(): string
    {
        return mb_substr($this->value, 0, 4);
    }

    /**
     * Get the country code (characters 5-6).
     */
    public function getCountryCode(): string
    {
        return mb_substr($this->value, 4, 2);
    }

    /**
     * Get the location code (characters 7-8).
     */
    public function getLocationCode(): string
    {
        return mb_substr($this->value, 6, 2);
    }

    /**
     * Get the branch code (characters 9-11), if present.
     */
    public function getBranchCode(): ?string
    {
        if (mb_strlen($this->value) === 11) {
            return mb_substr($this->value, 8, 3);
        }

        return null;
    }

    /**
     * Check if this is a primary office code (8 characters or ends in XXX).
     */
    public function isPrimaryOffice(): bool
    {
        return mb_strlen($this->value) === 8
            || mb_substr($this->value, 8, 3) === 'XXX';
    }

    /**
     * Check if this is a test/training code.
     *
     * Test codes have '0' as the second character of the location code.
     */
    public function isTestCode(): bool
    {
        return mb_substr($this->value, 7, 1) === '0';
    }

    /**
     * Check if this is a passive SWIFT participant.
     *
     * Passive participants have '1' as the second character of the location code.
     */
    public function isPassiveParticipant(): bool
    {
        return mb_substr($this->value, 7, 1) === '1';
    }

    /**
     * Get the 8-character version (primary office).
     */
    public function toPrimaryOffice(): self
    {
        if (mb_strlen($this->value) === 8) {
            return $this;
        }

        return new self(mb_substr($this->value, 0, 8));
    }

    /**
     * Get the 11-character version with XXX branch code.
     */
    public function toFullFormat(): self
    {
        if (mb_strlen($this->value) === 11) {
            return $this;
        }

        return new self($this->value . 'XXX');
    }

    /**
     * Get the formatted representation.
     */
    public function formatted(): string
    {
        if (mb_strlen($this->value) === 11) {
            return sprintf(
                '%s %s %s',
                mb_substr($this->value, 0, 4),
                mb_substr($this->value, 4, 4),
                mb_substr($this->value, 8, 3)
            );
        }

        return sprintf(
            '%s %s',
            mb_substr($this->value, 0, 4),
            mb_substr($this->value, 4, 4)
        );
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
     * Check equality with another SWIFT code.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Check if two SWIFT codes belong to the same bank.
     */
    public function isSameBank(self $other): bool
    {
        return $this->getBankCode() === $other->getBankCode()
            && $this->getCountryCode() === $other->getCountryCode();
    }

    /**
     * Validate the format of a SWIFT code.
     */
    private static function isValidFormat(string $value): bool
    {
        return preg_match(self::SWIFT_8_PATTERN, $value) === 1
            || preg_match(self::SWIFT_11_PATTERN, $value) === 1;
    }
}
