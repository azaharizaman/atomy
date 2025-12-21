<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\PaymentRails\Exceptions\InvalidRoutingNumberException;

/**
 * Represents a US ABA Routing Number (RTN).
 *
 * A routing number is a 9-digit code that identifies a US financial
 * institution. It's used for ACH transfers and wire transfers.
 *
 * The routing number follows the MICR format:
 * - First 4 digits: Federal Reserve routing symbol
 * - Next 4 digits: ABA institution identifier
 * - Last digit: Check digit (mod 10 algorithm)
 */
final class RoutingNumber
{
    /**
     * @var string The 9-digit routing number
     */
    public readonly string $value;

    /**
     * @throws InvalidRoutingNumberException
     */
    public function __construct(string $value)
    {
        $value = preg_replace('/[^0-9]/', '', $value) ?? '';

        if (mb_strlen($value) !== 9) {
            throw InvalidRoutingNumberException::invalidLength($value);
        }

        if (!self::validateCheckDigit($value)) {
            throw InvalidRoutingNumberException::invalidCheckDigit($value);
        }

        $this->value = $value;
    }

    /**
     * Create a routing number from a string value.
     *
     * @throws InvalidRoutingNumberException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create a routing number, returning null if invalid.
     */
    public static function tryFromString(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidRoutingNumberException) {
            return null;
        }
    }

    /**
     * Get the Federal Reserve routing symbol (first 4 digits).
     */
    public function getFederalReserveRoutingSymbol(): string
    {
        return mb_substr($this->value, 0, 4);
    }

    /**
     * Get the ABA institution identifier (next 4 digits).
     */
    public function getAbaInstitutionIdentifier(): string
    {
        return mb_substr($this->value, 4, 4);
    }

    /**
     * Get the check digit (last digit).
     */
    public function getCheckDigit(): int
    {
        return (int) mb_substr($this->value, 8, 1);
    }

    /**
     * Get the Federal Reserve District number.
     *
     * Based on the first two digits of the routing number.
     */
    public function getFederalReserveDistrict(): int
    {
        $first = (int) mb_substr($this->value, 0, 1);
        $second = (int) mb_substr($this->value, 1, 1);

        // Districts 1-12 are mapped from first digit
        if ($first >= 0 && $first <= 9) {
            if ($first === 0) {
                return $second === 0 ? 0 : ($second <= 2 ? $second : 12);
            }

            return $first;
        }

        return 0;
    }

    /**
     * Check if this is a routing number for a thrift institution.
     *
     * Thrift institutions (savings banks, S&Ls) have routing numbers
     * starting with 2, 3, 6, 7, or 8.
     */
    public function isThriftInstitution(): bool
    {
        $first = (int) mb_substr($this->value, 0, 1);

        return in_array($first, [2, 3, 6, 7, 8], true);
    }

    /**
     * Check if this routing number is valid for ACH transactions.
     *
     * Electronic transaction routing numbers start with 01-12, 21-32, 61-72.
     */
    public function isValidForAch(): bool
    {
        $prefix = (int) mb_substr($this->value, 0, 2);

        return ($prefix >= 1 && $prefix <= 12)
            || ($prefix >= 21 && $prefix <= 32)
            || ($prefix >= 61 && $prefix <= 72);
    }

    /**
     * Get the formatted routing number with dashes.
     */
    public function formatted(): string
    {
        return sprintf(
            '%s-%s-%s',
            mb_substr($this->value, 0, 4),
            mb_substr($this->value, 4, 4),
            mb_substr($this->value, 8, 1)
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
     * Check equality with another routing number.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Validate the check digit using the ABA algorithm.
     *
     * The algorithm multiplies each digit by a weight (3, 7, or 1),
     * sums the results, and checks if divisible by 10.
     */
    private static function validateCheckDigit(string $value): bool
    {
        $weights = [3, 7, 1, 3, 7, 1, 3, 7, 1];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $value[$i] * $weights[$i];
        }

        return $sum % 10 === 0;
    }
}
